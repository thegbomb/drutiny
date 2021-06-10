<?php

namespace Drutiny\Console\Command;

use Drutiny\Assessment;
use Drutiny\Policy;
use Drutiny\Profile;
use Drutiny\Target\Target;
use Drutiny\Report\Format\Terminal;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class AuditRunCommand extends DrutinyBaseCommand
{
  use ReportingCommandTrait;
  use LanguageCommandTrait;
  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('audit:run')
        ->setDescription('Run a single audit against a site without a policy.')
        ->addArgument(
            'audit',
            InputArgument::REQUIRED,
            'The PHP class (including namespace) of the audit'
        )
        ->addArgument(
            'target',
            InputArgument::REQUIRED,
            'The target to run the check against.'
        )
        ->addOption(
            'set-parameter',
            'p',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Set parameters for the check.',
            []
        )
        ->addOption(
            'remediate',
            'r',
            InputOption::VALUE_NONE,
            'Allow failed checks to remediate themselves if available.'
        )
        ->addOption(
            'uri',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Provide URLs to run against the target. Useful for multisite installs. Accepts multiple arguments.',
            'default'
        );
        parent::configure();
        $this->configureReporting();
        $this->configureLanguage();
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initLanguage($input);
        $container = $this->getApplication()
          ->getKernel()
          ->getContainer();

        $audit_class = $input->getArgument('audit');

        // Fabricate a policy to run the audit.
        $policy = new Policy();
        $policy->setProperties([
          'title' => 'Audit: ' . $audit_class,
          'name' => '_test',
          'class' => $audit_class,
          'description' => 'Verbatim run of an audit class',
          'remediation' => 'none',
          'success' => 'success',
          'failure' => 'failure',
          'warning' => 'warning',
          'uuid' => $audit_class,
          'severity' => 'normal'
        ]);

        // Setup any parameters for the check.
        foreach ($input->getOption('set-parameter') as $option) {
            list($key, $value) = explode('=', $option, 2);

            $info = Yaml::parse($value);

            $policy->addParameter($key, $info);
        }

        // Setup the target.
        $target = $container->get('target.factory')->create($input->getArgument('target'));

        // Setup the reporting report.
        $start = new \DateTime($input->getOption('reporting-period-start'));
        $end   = new \DateTime($input->getOption('reporting-period-end'));

        // If a URI is provided set it on the Target.
        if ($uri = $input->getOption('uri')) {
            $target->setUri($uri);
        }

        $assessment = $container->get('Drutiny\Assessment')->setUri($uri);
        $assessment->assessTarget($target, [$policy], $this->getReportingPeriodStart($input), $this->getReportingPeriodEnd($input), $input->getOption('remediate'));

        $profile = $container->get('profile.factory')->create([
          'title' => 'Audit:Run',
          'name' => 'audit_run',
          'uuid' => 'audit_run'
        ]);

        foreach ($this->getFormats($input, $profile) as $format) {
            $format->setNamespace($this->getReportNamespace($input, $uri));
            $format->render($profile, $assessment);
            foreach ($format->write() as $written_location) {
              // To nothing.
            }
        }

        return $assessment->getSeverityCode();
    }
}
