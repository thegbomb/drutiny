<?php

namespace Drutiny\Target\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RemoteService implements ExecutionInterface {

  protected $local;
  protected $sshConfig = [];

  public function __construct(LocalService $local)
  {
    $this->local = $local;
  }

  public function setConfig($key, $value)
  {
    $this->sshConfig[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function run(string $cmd, callable $preProcess = NULL, int $ttl = 3600)
  {
    $cmd = $this->local->replacePlaceholders($cmd);
    $cmd = sprintf(" 'echo %s | base64 --decode | sh'", base64_encode($cmd));
    return $this->local->run($this->getRemoteCall() . $cmd, $preProcess, $ttl);
  }

  /**
   * Formulate an SSH command. E.g. ssh -o User=foo hostname.bar
   */
  protected function getRemoteCall()
  {
    $args = ['ssh'];
    $options = $this->sshConfig;
    if (!isset($this->sshConfig['Host'])) {
      throw new \InvalidArgumentException("Missing 'Host' option in SSH Config.");
    }

    // Host is not a command line support option and must be passed as an argument.
    $host = $this->sshConfig['Host'];
    unset($options['Host']);

    foreach ($options as $key => $value) {
      $args[] = '-o';
      $args[] = sprintf('%s=%s', $key, $value);
    }
    $args[] = $host;
    return implode(' ', $args);
  }
}
