<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drutiny\PolicySource\PushablePolicySourceInterface;

/**
 *
 */
class ProfileSourcesCommand extends DrutinyBaseCommand
{
    use LanguageCommandTrait;
  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('profile:sources')
        ->setDescription('Show all profile sources.');
        $this->configureLanguage();
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getProfileFactory()->getSources() as $source) {
          $rows[] = [$source->getName(), get_class($source), $source->getWeight()];
        }

        $io = new SymfonyStyle($input, $output);
        $headers = ['Source', 'Class', 'Weight'];
        $io->table($headers, $rows);

        return 0;
    }
}
