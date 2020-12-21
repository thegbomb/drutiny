<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class PolicySourcesCommand extends DrutinyBaseCommand
{
    use LanguageCommandTrait;
  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('policy:sources')
        ->setDescription('Show all policy sources.');
        $this->configureLanguage();
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getPolicyFactory()->getSources() as $source) {
          $rows[] = [$source->getName(), get_class($source), $source->getWeight()];
        }

        $io = new SymfonyStyle($input, $output);
        $headers = ['Source', 'Class', 'Weight'];
        $io->table($headers, $rows);

        return 0;
    }

  /**
   *
   */
    protected function formatDescription($text)
    {
        $lines = explode(PHP_EOL, $text);
        $text = implode(' ', $lines);
        return wordwrap($text, 50);
    }
}
