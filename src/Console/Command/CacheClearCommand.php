<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Cache\Adapter\FilesystemAdapter as Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 *
 */
class CacheClearCommand extends Command
{
    protected $container;
    protected $cache;

    public function __construct(ContainerInterface $container, CacheInterface $cache)
    {
      $this->cache = $cache;
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
        ->addOption(
          'cid',
          null,
          InputOption::VALUE_OPTIONAL,
          'The cache ID to purge from cache.'
          )
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        if ($cid = $input->getOption('cid')) {
          $this->cache->delete($cid);
          $io->success('Cache item cleared: ' . $cid);
          return 0;
        }
        $fs['cache.directory'] = $this->container->getParameter('cache.directory');
        $fs['twig.cache'] = $this->container->getParameter('twig.cache');

        $cid = $input->getArgument('cache');

        switch ($cid) {
            case 'twig':
              $fs = [$fs['twig.cache']];
            case  NULL:
              break;
            default:
              $this->cache->delete($cid);
              $io->success('Cache item cleared: ' . $cid);
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
