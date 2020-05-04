<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Drutiny\Target\Registry as TargetRegistry;

/**
 *
 */
class TargetMetadataCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('target:metadata')
      ->setDescription('Display metatdata about a target.')
      ->addArgument(
        'target',
        InputArgument::REQUIRED,
        'A target reference.'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $target = TargetRegistry::loadTarget($input->getArgument('target'));


    $output->writeln('<comment>' . Yaml::dump($target->getMetadata()) . '</>');
  }

}
