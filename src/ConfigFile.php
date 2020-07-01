<?php

namespace Drutiny;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigFile
{
    protected $config;
    protected $filepath;
    protected $namespace;

    public function __construct($filepath)
    {
        $this->config = file_exists($filepath) ? Yaml::parseFile($filepath) : [];
        $this->filepath = $filepath;
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function doWrite()
    {
        return file_put_contents($this->filepath, Yaml::dump($this->config, 4, 4));
    }

    /**
     * Retrive a config value.
     */
    public function __get($key)
    {
        return $this->namespace ? $this->config[$this->namespace][$key] ?? null : $this->config[$key] ?? null;
    }

    public function __isset($key)
    {
        return $this->namespace ? isset($this->config[$this->namespace][$key]) : $this->config[$key];
    }

    public function __set($key, $value)
    {
      if (!$this->namespace) {
          $this->config[$key] = $value;
      }
      $this->config[$this->namespace][$key] = $value;
    }

    public function keys()
    {
        return $this->namespace ? array_keys($this->config[$this->namespace]) : array_keys($this->config);
    }
}
