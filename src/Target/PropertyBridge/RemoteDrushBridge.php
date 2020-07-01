<?php

namespace Drutiny\Target\PropertyBridge;

use Drutiny\Event\DataBagEvent;
use Drutiny\Target\Service\RemoteService;
use Drutiny\Target\Service\DrushService;
use Drutiny\Entity\Exception\DataNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RemoteDrushBridge implements EventSubscriberInterface {
  public static function getSubscribedEvents()
  {
    return [
    //  'set:service.drush' => 'loadRemoteDrushService',
      'set:drush.remote-user' => 'loadRemoteService',
      'set:drush.remote-host' => 'loadRemoteService',
      'set:drush.ssh-options' => 'parseSshOptions',
    ];
  }

  public static function parseSshOptions(DataBagEvent $event)
  {
      $options = [];
      $value = $event->getValue();
      $target = $event->getDatabag()->getObject();
      // Port parsing.
      if (preg_match('/-p (\d+)/', $value, $matches)) {
          $target['service.exec']->setConfig('Port', $matches[1]);
      }
      // IdentifyFile
      if (preg_match('/-i ([^ ]+)/', $value, $matches)) {
          $target['service.exec']->setConfig('IdentityFile', $matches[1]);
      }
      if (preg_match_all('/-o "([^ "]+) ([^"]+)"/', $value, $matches)) {
         foreach ($matches[1] as $idx => $key) {
           $target['service.exec']->setConfig($key, $matches[2][$idx]);
         }
      }
      if (preg_match_all('/-o ([^=]+)=([^ ]+)/', $value, $matches)) {
         foreach ($matches[1] as $idx => $key) {
           $target['service.exec']->setConfig($key, $matches[2][$idx]);
         }
      }
  }

  public static function loadRemoteService(DataBagEvent $event)
  {
      $target = $event->getDatabag()->getObject();
      $value = $event->getValue();
      try {
          switch ($event->getPropertyPath()) {
              case 'remote-user':
                  $user = $value;
                  $host = $target['drush.remote-host'];
                  break;
              case 'remote-host':
                  $user = $target['drush.remote-user'];
                  $host = $value;
                  break;
              default:
                  return;
          }
      }
      catch (DataNotFoundException $e) {
        return;
      }
      catch (\InvalidArgumentException $e) {
        return;
      }

      $remoteService = new RemoteService($target['service.local']);
      $remoteService->setConfig('User', $user);
      $remoteService->setConfig('Host', $host);
      $target['service.exec'] = $remoteService;
  }

  /**
   * Search drush config for remote ssh config.
   */
  public static function loadRemoteDrushService(DataBagEvent $event)
  {
    $service = $event->getValue();

    if (!($service instanceof DrushService)) {
      return;
    }
    if ($service->isRemote()) {
      return;
    }

    $target = $event->getDatabag()->getObject();

    try {
      $remoteService = new RemoteService($target['service.local']);
      $remoteService->setConfig('User', $target['drush.remote-user']);
      $remoteService->setConfig('Host', $target['drush.remote-host']);
      $target['service.exec'] = $remoteService;
      $event->setValue(new DrushService($remoteService));
    }
    // If the config doesn't exist then do nothing.
    catch (DataNotFoundException $e) {}
  }
}
