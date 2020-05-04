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
class AuthenticateCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('plugin:setup')
      ->setDescription('Register credentials against an API drutiny integrates with.')
      ->addArgument(
        'namespace',
        InputArgument::REQUIRED,
        'The service to authenticate against.',
      )
      ->addOption(
        'scope',
        's',
        InputOption::VALUE_OPTIONAL,
        'The scope to write the credential too. Options: user (default), local, global.',
        'user'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $helper = $this->getHelper('question');
    $namespace = $input->getArgument('namespace');
    $store = new FileStore($namespace);

    try {

      $store->open();
      $io->success("Credentials for $namespace already exist.");

      $update = $helper->ask($input, $output, new ConfirmationQuestion("Do you want to update the credentials? "));

      if (!$update) {
        return TRUE;
      }
    }
    catch (CredentialsUnavailableException $e) {
      // Creds don't exist yet.
    }

    $schema = (new Registry)->credentials();
    if (!isset($schema[$namespace])) {
      throw new CredentialsUnavailableException("Cannot find schema for $namespace.");
    }

    foreach ($schema[$namespace] as $name => $field) {
      $field['name'] = $name;
      $question = new Question(strtr("name (type)\ndescription : ", $field));
      $creds[$name] = $helper->ask($input, $output, $question);
    }

    $store->write($creds, $input->getOption('scope'));
    $io->success("Credentials for $namespace have been saved.");
  }

}
