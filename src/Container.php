<?php

namespace Drutiny;

use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Psr\Log\LoggerInterface;

class Container {

  public static function getCacheDirectory()
  {
    return Config::getUserDir() . '/cache';
  }

  public static function cache($bin)
  {
    $registry = Config::get('Cache');
    $cache = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
    if (isset($registry[$bin])) {
      $class = $registry[$bin];
      $cache = new $registry[$bin]($bin, 0, self::getCacheDirectory());
    }
    $cache->setLogger(self::getLogger());
    return $cache;
  }

  public static function config($bin)
  {
    return Config::get($bin);
  }

  public static function credentialManager($namespace)
  {
    return \Drutiny\Credential\Manager::load($namespace);
  }

  public static function utility()
  {
    return new Utility;
  }
}

 ?>
