<?php

namespace Drutiny\Console\Command;

use Drutiny\Assessment;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Policy;
use Drutiny\Profile;
use Drutiny\RemediableInterface;
use Drutiny\Report\ProfileRunReport;
use Drutiny\Target\Registry as TargetRegistry;
use Drutiny\Target\Target;
use Drutiny\Report\Format;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class AuditRunCommand extends AbstractReportingCommand
{

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
            'Provide URLs to run against the target. Useful for multisite installs. Accepts multiple arguments.'
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
        );
        parent::configure();
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $audit_class = $input->getArgument('audit');

        $policy = new Policy([
        'title' => 'Audit: ' . $audit_class,
        'name' => '_test',
        'class' => $audit_class,
        'description' => 'Verbatim run of an audit class',
        'remediation' => 'none',
        'success' => 'success',
        'failure' => 'failure',
        'warning' => 'warning',
        ]);

      // Setup any parameters for the check.
        foreach ($input->getOption('set-parameter') as $option) {
            list($key, $value) = explode('=', $option, 2);

            $info = ['default' => Yaml::parse($value)];
            $policy->addParameter($key, $info);
        }

      // Setup the target.
        $target = TargetRegistry::loadTarget($input->getArgument('target'));

        $start = new \DateTime($input->getOption('reporting-period-start'));
        $end   = new \DateTime($input->getOption('reporting-period-end'));

        if ($uri = $input->getOption('uri')) {
            $target->setUri($uri);
        }

        $assessment = new Assessment($input->getOption('uri'));
        $assessment->assessTarget($target, [$policy], $start, $end, $input->getOption('remediate'));

        $profile = new Profile();
        $profile->setTitle('Audit Run')
            ->setName('audit:run')
            ->setFilepath('/dev/null')
            ->addFormatOptions(Format::create('terminal', [
              'content' => Yaml::parseFile(dirname(__DIR__) . '/Report/templates/content/policy.markdown.yml')
            ]));

        if (!$input->getOption('report-filename')) {
            $input->setOption('report-filename', 'stdout');
        }
        if (!$input->getOption('format')) {
            $intput->setOption('format', 'terminal');
        }

        $this->report($profile, $input, $output, $target, [$assessment]);
    }
}
