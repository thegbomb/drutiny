<?php

namespace Drutiny\Console\Command;

use Drutiny\Assessment;
use Drutiny\Profile\ProfileSource;
use Drutiny\Profile\PolicyDefinition;
use Drutiny\Target\Registry as TargetRegistry;
use Drutiny\DomainSource;
use Drutiny\DomainList\DomainListRegistry;
use Drutiny\ProgressBar;
use Drutiny\PolicyFactory;
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
    protected $progress;
    protected $policyFactory;


    public function __construct(DomainSource $domainSource, ProgressBar $progress, PolicyFactory $factory)
    {
        $this->domainSource = $domainSource;
        $this->progress = $progress;
        $this->policyFactory = $factory;
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
            'none'
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

      // Set the filepath where the report will be written to (can be console).
        $filepath = $input->getOption('report-filename') ?: $this->getDefaultReportFilepath($input);

      // Setup the reporting format.
        $format = $input->getOption('format');
        $format = $container->get('format.factory')->create($format, $profile->getFormatOptions($format));

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

      // Setup the progress bar to log updates.
        $steps = count($policyDefinitions) * count($uris);

        $this->progress->resetSteps($steps);

      // We don't want to run the progress bar if the output is to stdout.
      // Unless the format is console/terminal as then the output doesn't matter.
      // E.g. turn of progress bar in json, html and markdown formats.
        if ($filepath == 'stdout' && !in_array($format->getFormat(), ['console', 'terminal'])) {
            $this->progress->disable();
        }
      // Do not use the progress bar when using a high verbosity logging output.
        elseif ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->progress->disable();
        }

        $results = [];

        $start = new \DateTime($input->getOption('reporting-period-start'));
        $end   = new \DateTime($input->getOption('reporting-period-end'));
        $profile->setReportingPeriod($start, $end);

        $policies = [];
        foreach ($policyDefinitions as $policyDefinition) {
            $policies[] = $policyDefinition->getPolicy($this->policyFactory);
        }

        $this->progress->start();

        foreach ($uris as $uri) {
            try {
                $target->setUri($uri);
            } catch (\Drutiny\Target\InvalidTargetException $e) {
                $container->get('logger')->warning("Target cannot be evaluated: " . $e->getMessage());
                $this->progress->advance(count($policyDefinitions));
                continue;
            }

            $results[$uri] = $container->get('Drutiny\Assessment')
            ->setUri($uri)
            ->assessTarget($target, $policies, $start, $end, $input->getOption('remediate'));
        }

        $this->progress->finish();

        if (!count($results)) {
            $container->get('logger')->error("No results were generated.");
            return;
        }

        $this->report($profile, $input, $output, $target, $results);

      // Do not use a non-zero exit code when no severity is set (Default).
        if (!$input->getOption('exit-on-severity')) {
            return;
        }

      // Return the max severity as the exit code.
        return max(array_map(function ($assessment) {
            return $assessment->getSeverityCode();
        }, $results));
    }

  /**
   * Determine a default filepath.
   */
    protected function getDefaultReportFilepath(InputInterface $input):string
    {
        $filepath = 'stdout';
      // If format is not out to console and the filepath isn't set, automate
      // what the filepath should be.
        if (!in_array($input->getOption('format'), ['console', 'terminal'])) {
            $filepath = strtr('target-profile-date.format', [
             'target' => preg_replace('/[^a-z0-9]/', '', strtolower($input->getArgument('target'))),
             'profile' => $input->getArgument('profile'),
             'date' => date('Ymd-His'),
             'format' => $input->getOption('format')
            ]);
        }
        return $filepath;
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
