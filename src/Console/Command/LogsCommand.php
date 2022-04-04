<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 *
 */
class LogsCommand extends DrutinyBaseCommand
{

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('logs')
        ->setDescription('Show recent logs from current day.')
        ->addOption(
          'tail',
          'f',
          InputOption::VALUE_NONE
        );
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logfile = $this->getContainer()->get('logger.logfile');
        if ($input->getOption('tail')) {
          passthru(sprintf('tail -f -n 20 %s', $logfile->getUrl()));
        }
        else {
          passthru(sprintf('cat %s', $logfile->getUrl()));
        }
        return 0;
    }
}
