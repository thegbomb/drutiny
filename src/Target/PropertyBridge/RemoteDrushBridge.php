<?php

namespace Drutiny\Target\PropertyBridge;

use Drutiny\Event\TargetPropertyBridgeEvent;
use Drutiny\Target\Bridge\RemoteBridge;
use Drutiny\Target\Bridge\Drush\DrushBridge;
use Drutiny\Entity\Exception\DataNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RemoteDrushBridge implements EventSubscriberInterface {
  public static function getSubscribedEvents()
  {
    return ['target.property.bridge.drush' => 'loadRemoteBridge'];
  }

  /**
   * Search drush config for remote ssh config.
   */
  public static function loadRemoteBridge(TargetPropertyBridgeEvent $event)
  {
    $bridge = $event->getValue();

    if (!($bridge instanceof DrushBridge)) {
      return;
    }
    if ($bridge->getExecBridge() instanceof RemoteBridge) {
      return;
    }

    $target = $event->getTarget();

    try {
      $remoteBridge = new RemoteBridge($target->getBridge('exec'));
      $remoteBridge->setConfig('User', $target->getProperty('drush.remote-user'));
      $remoteBridge->setConfig('Host', $target->getProperty('drush.remote-host'));
      // TODO: ssh-options
      $bridge->setExecBridge($remoteBridge);
      $target->setExecBridge($remoteBridge);
    }
    // If the config doesn't exist then do nothing.
    catch (DataNotFoundException $e) {}
  }
}
