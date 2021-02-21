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
class HttpAuthSetupCommand extends Command
{
    protected $container;
    protected $credentials;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->credentials = $container->get('credentials')->load('http');
        parent::__construct();
    }

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('http-auth:setup')
        ->setDescription('Register API credentials for a site factory.')
        ->addArgument(
            'host',
            InputArgument::REQUIRED,
            'The domain name for the basic auth credentials.',
        )
        ->addOption(
            'username',
            'u',
            InputOption::VALUE_OPTIONAL,
            'A username for the basic auth credentials.'
        )
        ->addOption(
            'password',
            'p',
            InputOption::VALUE_OPTIONAL,
            'A password for the basic auth credentials.'
        );
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $list = $this->credentials->authorization ?? [];
        $list[$input->getArgument('host')] = [
          'username' => $input->getOption('username') ?? $io->ask("Username"),
          'password' => $input->getOption('password') ?? $io->askHidden("Password"),
        ];

        $this->credentials->authorization = $list;

        $io->success("Basic auth credentials for ".$input->getArgument('host')." have been saved.");
        return 0;
    }
}
