<?php

namespace Drutiny\Target;

use Drutiny\Target\Service\DrushService;
use Drutiny\Target\Service\DockerService;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Target for parsing Drush aliases.
 */
class LandoTarget extends DrushTarget implements TargetInterface, TargetSourceInterface
{
  /**
   * {@inheritdoc}
   */
  public function getId():string
  {
    return $this['ddev.name'];
  }

  /**
   * @inheritdoc
   * Implements Target::parse().
   */
    public function parse($alias):TargetInterface
    {

        $this['lando.name'] = $alias;

        $lando = $this['service.local']->run('lando list --format=json', function ($output) {
          return json_decode($output, true);
        });

        $apps = array_filter($lando, function ($instance) use ($alias) {
          return ($instance['service'] == 'appserver') && ($instance['app'] == $alias);
        });

        if (empty($apps)) {
          throw new InvalidTargetException("Lando site '$alias' either doesn't exist or is not currently active.");
        }

        $this['lando.app'] = array_shift($apps);
        $this['service.docker'] = new DockerService($this['service.local']);
        $this['service.docker']->setContainer($this['lando.app']['name']);
        $this['service.exec'] = $this['service.docker'];

        $this['drush.root'] = '/app';

        $dir = dirname($this['lando.app']['src'][0]);
        $edge = $this['service.local']->run(sprintf('cd %s && lando info --format=json -s edge', $dir), function ($output) {
          return json_decode($output, true);
        });

        $this['lando.edge'] = $edge[0];

        // Provide a default URI if none already provided.
        $this->setUri($edge[0]['urls'][1] ?? $edge[0]['urls'][0]);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTargets():array
    {
      $lando = $this['service.local']->run('lando list --format=json', function ($output) {
        return json_decode($output, true);
      });

      $apps = array_filter($lando, function ($instance) {
        return $instance['service'] == 'appserver';
      });

      $targets = [];
      foreach ($apps as $app) {
        $dir = dirname($app['src'][0]);
        $edge = $this['service.local']->run(sprintf('cd %s && lando info --format=json -s edge', $dir), function ($output) {
          return json_decode($output, true);
        });

        $targets[] = [
          'id' => $app['app'],
          'uri' => $edge[0]['urls'][1] ?? $edge[0]['urls'][0],
          'name' => $app['app']
        ];
      }
      return $targets;
    }
}
