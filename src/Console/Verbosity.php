<?php

namespace Drutiny\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

/**
 * Control the verbosity of output in Drutiny.
 */
class Verbosity
{
    private $verbosity;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
      $this->logger = $logger;
    }

  /**
   * {@inheritdoc}
   */
    public function set($level)
    {
        $this->verbosity = (int) $level;
        $this->logger->setLevel($level);
    }

  /**
   * {@inheritdoc}
   */
    public function get()
    {
        if (!isset($this->verbosity)) {
            switch (getenv('SHELL_VERBOSITY')) {
                case -1:
                    return OutputInterface::VERBOSITY_QUIET;
                break;
                case 1:
                    return OutputInterface::VERBOSITY_VERBOSE;
                break;
                case 2:
                    return OutputInterface::VERBOSITY_VERY_VERBOSE;
                break;
                case 3:
                    return OutputInterface::VERBOSITY_DEBUG;
                break;
                default:
                    return OutputInterface::VERBOSITY_NORMAL;
                break;
            }
        }
        return $this->verbosity;
    }
}
