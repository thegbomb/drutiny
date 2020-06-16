<?php

namespace Drutiny\Target;

use Drutiny\Target\Service\DrushService;

/**
 * Target for parsing Drush aliases.
 */
class DrushTarget extends Target implements TargetInterface
{
  /**
   * @inheritdoc
   * Implements Target::parse().
   */
    public function parse($alias):TargetInterface
    {

        $this['drush.alias'] = $alias;

        $status_cmd = 'drush site:alias $DRUSH_ALIAS --format=json';
        $drush_properties = $this['service.local']->run($status_cmd, function ($output) use ($alias) {
          $json = json_decode($output, true);
          $index = substr($alias, 1);
          return $json[$index] ?? array_shift($json);
        });

        $this['drush']->add($drush_properties);

        if (isset($drush_properties['uri'])) {
          $this['uri'] = $drush_properties['uri'];
        }

        $this->buildAttributes();
        return $this;
    }

    public function buildAttributes() {
        $service = new DrushService($this['service.exec']);

        $status = $service->status(['format' => 'json'])
           ->run(function ($output) {
             return json_decode($output, TRUE);
           });

        foreach ($status as $key => $value) {
          $this['drush.'.$key] = $value;
        }

        $this['service.drush'] = $service;

        $version = $this['service.exec']->run('php -v | head -1 | awk \'{print $2}\'');
        $this['php_version'] = trim($version);

        return $this;
    }
}
