<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Cache\Adapter\FilesystemAdapter as Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class CacheClearCommand extends Command
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
      $this->container = $container;
      parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
        ->setName('cache:clear')
        ->setDescription('Clear the Drutiny cache')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = $this->container->getParameter('cache.directory');
        $io = new SymfonyStyle($input, $output);


        if (!file_exists($fs)) {
          $io->notice('Cache is already cleared.');
          return 0;
        }
        if (!is_writable($fs)) {
          $io->error(sprintf('Cannot clear cache: %s is not writable.', $fs));
          return 1;
        }
        exec(sprintf('rm -rf %s', $fs), $output, $status);
        if ($status === 0) {
          $io->success('Cache is cleared.');
          return 0;
        }
        $io->error(sprintf('Cannot clear cache from %s. An error occured.', $fs));
        return $status;
    }
}
