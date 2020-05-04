<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Drutiny\Registry;
use Drutiny\Config;
use Drutiny\PolicySource\PolicySource;
use Drutiny\ProgressBar;

/**
 *
 */
class PolicyListCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('policy:list')
      ->setDescription('Show all policies available.')
      ->addOption(
        'filter',
        't',
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Filter list by tag'
      )
      ->addOption(
        'source',
        's',
        InputOption::VALUE_OPTIONAL,
        'Filter by source'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $progress = new ProgressBar($output, 2);
    $progress->setTopic("Loading policy data");
    $progress->start();
    $list = PolicySource::getPolicyList();
    $progress->advance();

    if ($source_filter = $input->getOption('source')) {
      $list = array_filter($list, function ($policy) use ($source_filter) {
        return $source_filter == $policy['source'];
      });
    }

    $rows = array();
    foreach ($list as $listedPolicy) {
      $row = array(
        'description' => '<options=bold>' . wordwrap($listedPolicy['title'], 50) . '</>',
        'name' => $listedPolicy['name'],
        'source' => $listedPolicy['source'],
      );
      if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
        $row['filename'] = $listedPolicy['filepath'];
      }
      $rows[] = $row;
    }

    usort($rows, function ($a, $b) {
      $x = [strtolower($a['name']), strtolower($b['name'])];
      sort($x, SORT_STRING);

      return $x[0] == strtolower($a['name']) ? -1 : 1;
    });

    $io = new SymfonyStyle($input, $output);
    $headers = ['Title', 'Name', 'Source'];
    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
      $headers[] = 'URI';
    }
    $progress->finish();
    $io->table($headers, $rows);
  }

  /**
   *
   */
  protected function formatDescription($text) {
    $lines = explode(PHP_EOL, $text);
    $text = implode(' ', $lines);
    return wordwrap($text, 50);
  }

}
