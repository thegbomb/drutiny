<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
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
        ->addArgument(
          'cache',
          InputArgument::OPTIONAL,
          'A cache reference to purge (e.g. twig).'
          )
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs['cache.directory'] = $this->container->getParameter('cache.directory');
        $fs['twig.cache'] = $this->container->getParameter('twig.cache');
        $io = new SymfonyStyle($input, $output);

        switch ($input->getArgument('cache')) {
            case 'twig':
              $fs = [$fs['twig.cache']];
            default:
              break;
        }

        foreach ($fs as $dir) {
          if (!file_exists($dir)) {
            $io->error('Cache is already cleared: ' . $dir);
            continue;
          }
          if (!is_writable($dir)) {
            $io->error(sprintf('Cannot clear cache: %s is not writable.', $dir));
            continue;
          }
          exec(sprintf('rm -rf %s', $dir), $output, $status);
          if ($status === 0) {
            $io->success('Cache is cleared: ' . $dir);
            continue;
          }
          $io->error(sprintf('Cannot clear cache from %s. An error occured.', $dir));
        }
        return 0;
    }
}
