<?php

namespace Drutiny\Process;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Contracts\Cache\CacheInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use Psr\Log\LoggerInterface;

class ProcessManager
{
    use LoggerAwareTrait;
    use LoggerTrait;

    protected $process;
    protected $cache;

    public function __construct(CacheInterface $cache, LoggerInterface $logger)
    {
        $this->cache = $cache;
        $this->setLogger($logger);
    }

  /**
   * @param array          $command The command to run and its arguments listed as separate entries
   * @param string|null    $cwd     The working directory or null to use the working dir of the current PHP process
   * @param array|null     $env     The environment variables or null to use the same environment as the current PHP process
   * @param mixed|null     $input   The input as stream resource, scalar or \Traversable, or null for no input
   * @param int|float|null $timeout The timeout in seconds or null to disable
   *
   * @throws LogicException When proc_open is not installed
   */
    public function exec(array $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60)
    {
        $this->info(__CLASS__ . ': Running process: ' . implode(' ', $command));
        $process = new Process($command, $cwd, $env, $input, $timeout);
        $process->setTimeout(600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $output = $process->getOutput();
        $this->debug(__CLASS__ . ': output: ' . $output);
        return $output;
    }

  /**
   * {@inherit}
   */
    public function log($level, $message, array $context = array())
    {
        $this->logger->log($level, $message, $context);
    }
}
