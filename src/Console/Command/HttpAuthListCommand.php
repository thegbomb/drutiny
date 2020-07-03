<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class HttpAuthListCommand extends Command
{
    protected $container;
    protected $credentials;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->credentials = $container->get('credentials')->setNamespace('http');
        parent::__construct();
    }

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('http-auth:list')
        ->setDescription('List the hostname and usernames registered for http auth.');
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = $this->credentials->authorization ?? [];
        $io = new SymfonyStyle($input, $output);

        if (empty($list)) {
            $io->warning("There are currently no hostnames registered for HTTP auth.");
            return 0;
        }
        $io->table(['Host', 'Username'], array_map(function ($key) use ($list) {
            return [$key, $list[$key]['username']];
        }, array_keys($list)));
        return 0;
    }
}
