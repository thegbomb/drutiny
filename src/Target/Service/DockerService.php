<?php

namespace Drutiny\Target\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DockerService implements ExecutionInterface {
  protected $local;
  protected $container;

  public function __construct(LocalService $local)
  {
    $this->local = $local;
  }

  public function setContainer(string $container_name):ExecutionInterface
  {
    $this->container = $container_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function run(string $cmd, callable $preProcess = NULL, int $ttl = 3600)
  {
    $cmd = $this->local->replacePlaceholders($cmd);
    $cmd = sprintf("docker exec -t %s sh -c 'echo %s | base64 --decode | sh'", $this->container, base64_encode($cmd));

    return $this->local->run($cmd, $preProcess, $ttl);
  }

  /**
   * {@inheritdoc}
   */
  public function hasEnvVar($name):bool
  {
    return $this->local->hasEnvVar($name);
  }
}
