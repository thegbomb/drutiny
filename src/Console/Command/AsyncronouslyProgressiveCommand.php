<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for building checks.
 */
abstract class AsyncronouslyProgressiveCommand extends Command
{


    protected $logger;
    private $asyncRuntimeArray = [];

    public function __construct()
    {
        $this->logger = $this->getContainer()->get('async.logger');
    }

    /**
     * Alias to get service container.
     */
    protected function getContainer():ContainerInterface
    {
        return $this->getApplication()
          ->getKernel()
          ->getContainer();
    }
}
