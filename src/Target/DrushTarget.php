<?php

namespace Drutiny\Target;

use Drutiny\Target\Bridge\Drush\DrushBridge;

/**
 * Target for parsing Drush aliases.
 */
class DrushTarget extends Target implements TargetInterface
{
    protected $alias;

  /**
   * @inheritdoc
   * Implements Target::parse().
   */
    public function parse($alias):TargetInterface
    {
        $this->alias = $alias;
        $this->createProperty('drush')
          ->setProperty('drush.alias', $this->alias);

        $status_cmd = 'drush site:alias $DRUSH_ALIAS --format=json';
        $execBridge = $this->getProperty('bridge.local');
        $drush_properties = $execBridge->run($status_cmd, function ($output) use ($alias) {
          $json = json_decode($output, true);
          $index = substr($alias, 1);
          return $json[$index] ?? array_shift($json);
        });

        foreach ($drush_properties as $key => $value) {
          $this->setProperty('drush.'.$key, $value);
        }

        if (isset($drush_properties['uri'])) {
          $this->setProperty('uri', $drush_properties['uri']);
        }

        $this->setProperty('bridge.drush', new DrushBridge($execBridge));

        return $this;
    }

  /**
   * {@inheritdoc}
   */
    public function getOptions()
    {
        return $this->getProperty('drush');
    }

  /**
   * {@inheritdoc}
   */
    public function getAlias()
    {
        return $this->alias;
    }
}
