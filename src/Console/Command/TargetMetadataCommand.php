<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Drutiny\Console\ProgressLogger;

/**
 *
 */
class TargetMetadataCommand extends Command
{

    protected $progressLogger;


    public function __construct(ProgressLogger $progressLogger)
    {
        $this->progressLogger = $progressLogger;
        parent::__construct();
    }

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('target:info')
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->progressLogger->flushBuffer();
        $target = $this->getApplication()
          ->getKernel()
          ->getContainer()
          ->get('target.factory')
          ->create($input->getArgument('target'));

        $io = new SymfonyStyle($input, $output);

        $rows = [];

        foreach ($target->getPropertyList() as $key) {
          $value = $target->getProperty($key);
          $value = is_object($value) ? '<object> (' . get_class($value) . ')'  : '<'.gettype($value) . '> ' . Yaml::dump($value, 4, 4);
          $rows[] = [$key, $value];
        }
        $io->table(['Property', 'Value'], $rows);

        return 0;
    }
}
