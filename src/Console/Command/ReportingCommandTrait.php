<?php

namespace Drutiny\Console\Command;

use Drutiny\Report\FormatInterface;
use Drutiny\Report\FilesystemFormatInterface;
use Drutiny\Profile;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

/**
 *
 */
trait ReportingCommandTrait
{
  /**
   * @inheritdoc
   */
    protected function configureReporting()
    {
        $this
        ->addOption(
            'format',
            'f',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Specify which output format to render the report (terminal, html, json). Defaults to terminal.',
            ['terminal']
        )
        ->addOption(
            'report-dir',
            'o',
            InputOption::VALUE_OPTIONAL,
            'For file based formats, use this option to write report to a file directory. Drutiny will automate a filepath if the option is omitted',
            getenv('PWD')
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
    }

    /**
     * Determine a default filepath.
     */
      protected function getReportNamespace(InputInterface $input, $uri = ''):string
      {
          return strtr('target-profile-uri-date', [
            'uri' => $uri,
            'target' => preg_replace('/[^a-z0-9]/', '', strtolower($input->getArgument('target'))),
            'profile' => $input->hasArgument('profile') ? $input->getArgument('profile') : '',
            'date' => date('Ymd-His'),
          ]);
      }

      protected function getFormats(InputInterface $input, Profile $profile = null):array
      {
        foreach ($input->getOption('format') as $format_option) {
          $formats[$format_option] = $this->getContainer()
            ->get('format.factory')
            ->create($format_option, $profile->format[$format_option] ?? []);

          if ($formats[$format_option] instanceof FilesystemFormatInterface) {
            $formats[$format_option]->setWriteableDirectory($input->getOption('report-dir'));
          }
        }
        return $formats;
      }
}
