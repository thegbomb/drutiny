<?php

namespace Drutiny;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ImmuntableConfig
{

    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public static function create(ContainerInterface $container)
    {
        return new static($container->get('config'));
    }

  /**
   * Travese the config array as a new config object.
   */
    public function getConfig($key)
    {
        return new static($this->config[$key] ?? false);
    }

  /**
   * Retrive a config value.
   */
    public function __get($key)
    {
        return $this->config[$key] ?? null;
    }
}
