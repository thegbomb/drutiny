<?php

namespace Drutiny\Target\PropertyBridge;

use Drutiny\Event\DataBagEvent;
use Drutiny\Target\Service\RemoteService;
use Drutiny\Entity\Exception\DataNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RemoteDrushBridge implements EventSubscriberInterface {
  public static function getSubscribedEvents()
  {
    return [
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

      // Do nothing if a RemoteService is already loaded.
      // This allow extended classes to set a different RemoteService
      // as long as it extends the RemoteService class.
      if ($target['service.exec'] instanceof RemoteService) {
        return;
      }

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
      // Not enough data to build the RemoteService yet.
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
}
