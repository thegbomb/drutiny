<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Drutiny\PolicySource\PolicySource;
use Drutiny\Profile;
use Drutiny\Config;

/**
 *
 */
class PolicyDownloadCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('policy:download')
      ->setDescription('Download a remote policy locally.')
      ->addArgument(
        'policy',
        InputArgument::REQUIRED,
        'The name of the profile to download.'
      )
      ->addArgument(
        'source',
        InputArgument::OPTIONAL,
        'The source to download the profile from.'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $render = new SymfonyStyle($input, $output);
    $policy = PolicySource::loadPolicyByName($name = $input->getArgument('policy'));

    $name = str_replace(':', '-', $name);
    $filename = Config::getUserDir() . "/$name.policy.yml";
    if (file_exists($filename)) {
      $render->error("$filename already exists. Please delete this file if you wish to download it from its source.");
      return;
    }

    $output = Yaml::dump($policy->export(), 6, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

    file_put_contents($filename, $output);
    $render->success("$filename written.");
  }

}
