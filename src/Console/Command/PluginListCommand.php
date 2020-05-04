<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Drutiny\Registry;
use Drutiny\Credential\FileStore;
use Drutiny\Credential\CredentialsUnavailableException;

/**
 *
 */
class PluginListCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('plugin:list')
      ->setDescription('List all available plugins.');
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $schema = (new Registry)->credentials();

    foreach (array_keys($schema) as $namespace) {
      try {
        $store = new FileStore($namespace);
        $store->open();
        $status = 'installed';
      }
      catch (CredentialsUnavailableException $e) {
        $status = 'not installed';
      }

      $rows[] = [
        'namespace' => $namespace,
        'status' => $status,
      ];
    }
    $io->table(['Namespace', 'Status'], $rows);
  }

}
