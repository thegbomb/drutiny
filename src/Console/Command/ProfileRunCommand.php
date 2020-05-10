<?php

namespace Drutiny\Console\Command;

use Drutiny\Assessment;
use Drutiny\Console\ProgressLogger;
use Drutiny\Profile\ProfileSource;
use Drutiny\Profile\PolicyDefinition;
use Drutiny\DomainSource;
use Drutiny\PolicyFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Application;

/**
 * Run a profile and generate a report.
 */
class ProfileRunCommand extends AbstractReportingCommand
{

    protected $domainSource;
    protected $logger;
    protected $progressLogger;
    protected $policyFactory;


    public function __construct(DomainSource $domainSource, LoggerInterface $logger, ProgressLogger $progressLogger, PolicyFactory $factory)
    {
        $this->domainSource = $domainSource;
        $this->logger = $logger;
        $this->policyFactory = $factory;
        $this->progressLogger = $progressLogger;
        parent::__construct();
    }

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        parent::configure();

        $this
        ->setName('profile:run')
        ->setDescription('Run a profile of checks against a target.')
        ->addArgument(
            'profile',
            InputArgument::REQUIRED,
            'The name of the profile to run.'
        )
        ->addArgument(
            'target',
            InputArgument::REQUIRED,
            'The target to run the policy collection against.'
        )
        ->addOption(
            'remediate',
            'r',
            InputOption::VALUE_NONE,
            'Allow failed policy aduits to remediate themselves if available.'
        )
        ->addOption(
            'uri',
            'l',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Provide URLs to run against the target. Useful for multisite installs. Accepts multiple arguments.',
            ['default']
        )
        ->addOption(
            'exit-on-severity',
            'x',
            InputOption::VALUE_OPTIONAL,
            'Send an exit code to the console if a policy of a given severity fails. Defaults to none (exit code 0). (Options: none, low, normal, high, critical)',
            FALSE
        )
        ->addOption(
            'exclude-policy',
            'e',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Specify policy names to exclude from the profile that are normally listed.',
            []
        )
        ->addOption(
            'include-policy',
            'p',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Specify policy names to include in the profile in addition to those listed in the profile.',
            []
        )
        ->addOption(
            'reporting-period-start',
            null,
            InputOption::VALUE_OPTIONAL,
            'The starting point in time to report from. Can be absolute or relative. Defaults to 24 hours before the current hour.',
            date('Y-m-d H:00:00', strtotime('-24 hours'))
        )
        ->addOption(
            'reporting-period-end',
            null,
            InputOption::VALUE_OPTIONAL,
            'The end point in time to report to. Can be absolute or relative. Defaults to the current hour.',
            date('Y-m-d H:00:00')
        )
        ->addOption(
            'domain-source',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Use a domain source to preload uri options. Defaults to yaml filepath.'
        )->addOption(
            'domain-source-blacklist',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Exclude domains that match this regex filter',
            []
        )
        ->addOption(
            'domain-source-whitelist',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Exclude domains that don\'t match this regex filter',
            []
        );
    }

  /**
   * {@inheritdoc}
   */
    public function setApplication(?Application $application = null)
    {
        parent::setApplication($application);

      // Build a way for the command line to specify the options to derive
      // domains from their sources.
        foreach ($this->domainSource->getSources() as $driver => $properties) {
            foreach ($properties as $name => $description) {
                $this->addOption(
                    'domain-source-' . $driver . '-' . $name,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    $description
                );
            }
        }
    }

  /**
   * {@inheritdoc}
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $container = $this->getApplication()
        ->getKernel()
        ->getContainer();

        // Ensure Container logger uses the same verbosity.
        $container->get('verbosity')
        ->set($output->getVerbosity());

        $console = new SymfonyStyle($input, $output);

        $profile = $container->get('profile.factory')
        ->loadProfileByName($input->getArgument('profile'))
        ->setReportPerSite($input->getOption('report-per-site'));

        // Override the title of the profile with the specified value.
        if ($title = $input->getOption('title')) {
            $profile->setTitle($title);
        }

        // Setup the reporting format.
        $format = $input->getOption('format');
        $format = $container->get('format.factory')->create($format, $profile->getFormatOptions($format));

        // Set the filepath where the report will be written to (can be console).
        $filepath = $input->getOption('report-filename') ?: $this->getDefaultReportFilepath($input, $format);

        // If we're echoing to standard out, then we can run the logger and
        // progress indicator without compromising output formats such as json
        // and HTML.
        if ($filepath != 'stdout') {
          $this->progressLogger->flushBuffer();
        }

      // Allow command line to add policies to the profile.
        $included_policies = $input->getOption('include-policy');
        foreach ($included_policies as $policy_name) {
            $policyDefinition = PolicyDefinition::createFromProfile($policy_name, count($profile->getAllPolicyDefinitions()));
            $profile->addPolicyDefinition($policyDefinition);
        }

      // Allow command line omission of policies highlighted in the profile.
      // WARNING: This may remove policy dependants which may make polices behave
      // in strange ways.
        $excluded_policies = $input->getOption('exclude-policy');
        $policyDefinitions = array_filter($profile->getAllPolicyDefinitions(), function ($policy) use ($excluded_policies) {
            return !in_array($policy->getName(), $excluded_policies);
        });

        // Setup the target.
        $target = $container->get('target.factory')->create($input->getArgument('target'));

        // Get the URLs.
        $uris = $input->getOption('uri');

        $domains = [];
        foreach ($this->parseDomainSourceOptions($input) as $source => $options) {
            $domains = array_merge($this->domainSource->getDomains($source, $options), $domains);
        }

        if (!empty($domains)) {
          // Merge domains in with the $uris argument.
          // Omit the "default" key that is present by default.
            $uris = array_merge($domains, ($uris === ['default']) ? [] : $uris);
        }

        $results = [];

        $start = new \DateTime($input->getOption('reporting-period-start'));
        $end   = new \DateTime($input->getOption('reporting-period-end'));
        $profile->setReportingPeriod($start, $end);

        $policies = [];
        foreach ($policyDefinitions as $policyDefinition) {
            $policies[] = $policyDefinition->getPolicy($this->policyFactory);
        }

        foreach ($uris as $uri) {
            try {
                $this->logger->setTopic("Evaluating ".$profile->getName()." against $uri.");
                $target->setUri($uri);
            } catch (\Drutiny\Target\InvalidTargetException $e) {
                $this->logger->warning("Target cannot be evaluated: " . $e->getMessage());
                continue;
            }

            $results[$uri] = $container->get('Drutiny\Assessment')
            ->setUri($uri)
            ->assessTarget($target, $policies, $start, $end, $input->getOption('remediate'));
        }

        if (!count($results)) {
            $this->logger->error("No results were generated.");
            return 101;
        }
        $this->logger->setTopic("Building report for " . $format->getFormat());
        $this->report($profile, $input, $output, $target, $results);

        // Do not use a non-zero exit code when no severity is set (Default).
        $exit_severity = $input->getOption('exit-on-severity');
        if ($exit_severity === FALSE) {
            return 0;
        }
        $this->logger->info("Exiting with max severity code.");

        // Return the max severity as the exit code.
        $exit_code = max(array_map(function ($assessment) {
            return $assessment->getSeverityCode();
        }, $results));

        return $exit_code >= $exit_severity ? $exit_code : 0;
    }

    protected function parseDomainSourceOptions(InputInterface $input):array
    {
      // Load additional uris from domain-source
        $sources = [];
        foreach ($input->getOptions() as $name => $value) {
            if ($value === null) {
                continue;
            }
            if (strpos($name, 'domain-source-') === false) {
                continue;
            }
            $param = str_replace('domain-source-', '', $name);
            if (strpos($param, '-') === false) {
                continue;
            }
            list($source, $name) = explode('-', $param, 2);
            $sources[$source][$name] = $value;
        }
        return $sources;
    }
}
