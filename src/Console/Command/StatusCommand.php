<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 *
 */
class StatusCommand extends Command
{

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('status')
        ->setDescription('Review key details about Drutiny\'s runtime environment')
        ;
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output):int
    {
        $style = new SymfonyStyle($input, $output);

        $headers = ['Criteria', 'Status', 'Details'];
        $rows = [];

      // PHP version
        $rows[] = [
        'PHP version',
        phpversion(),
        'Drutiny requires PHP 7.4 or later.'
        ];

      // PHP Memory Limit
        $rows[] = [
        'PHP Memory Limit',
        ini_get('memory_limit'),
        'Drutiny recommends no memory limit (-1)'
        ];

        $style->table($headers, $rows);
        return 0;
    }
}
