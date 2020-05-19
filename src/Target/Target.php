<?php

namespace Drutiny\Target;

use Drutiny\Event\TargetPropertyBridgeEvent;
use Drutiny\Entity\DataBag;
use Drutiny\Target\Bridge\ExecutionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Log\LoggerInterface;

/**
 * Basic function of a Target.
 */
abstract class Target
{

  /* @var PropertyAccess */
  protected $propertyAccessor;
  protected $properties;
  protected $local;
  protected $logger;
  protected $dispatcher;

  public function __construct(Bridge\LocalBridge $local, LoggerInterface $logger, EventDispatcher $dispatcher)
  {
    $this->dispatcher = $dispatcher;
    $this->logger = $logger;
    $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
      ->enableExceptionOnInvalidIndex()
      ->getPropertyAccessor();
    $this->properties = new DataBag();
    $this->properties->onSet(function ($k, $v) {
      return $this->emitProperty($k, $v);
    });

    $local->setTarget($this);

    $bridge = $this->createProperty('bridge');
    $bridge->add([
      'local' => $local,
      'exec' => $local
    ]);

  }

  /**
   * {@inheritdoc}
   */
  public function setUri(string $uri)
  {
    return $this->setProperty('uri', $uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getUri()
  {
    return $this->getProperty('uri');
  }

  /**
   * {@inheritdoc}
   */
  public function getBridge($key)
  {
    return $this->getProperty('bridge.'.$key);
  }

  /**
   * Allow the execution bridge to change depending on the target environment.
   */
  public function setExecBridge(ExecutionInterface $bridge)
  {
    return $this->setProperty('bridge.exec', $bridge);
  }

  protected function createProperty($key)
  {
    $bag = new DataBag();
    $bag->onSet(function ($k, $v) use ($key) {
      return $this->emitProperty($key.'.'.$k, $v);
    });

    $this->properties->set($key, $bag);

    return $bag;
  }

  private function emitProperty($key, $value)
  {
    // Allow property bridges to change the value.
    $event = new TargetPropertyBridgeEvent($this, $key, $value);
    $event_name = 'target.property.'.$key;

    $this->logger->debug("Firing event '$event_name' from ".static::class);
    $this->dispatcher->dispatch($event, $event_name);
    $value = $event->getValue();
    // $this->logger->debug("Setting ".static::class." property '$key' with value of type " . gettype($value));

    return $value;
  }

  /**
   * Set a property.
   */
  protected function setProperty($key, $value)
  {
    $this->propertyAccessor->setValue($this->properties, $key, $value);
    return $this;
  }

  /**
   * Get a set property.
   *
   * @exception NoSuchIndexException
   */
  public function getProperty($key)
  {
    return $this->propertyAccessor->getValue($this->properties, $key);
  }

  /**
   * Get a list of properties available.
   */
  public function getPropertyList()
  {
    $paths = $this->getDataPaths($this->properties);
    sort($paths);
    return $paths;
  }

  private function getDataPaths(Databag $bag, $prefix = '') {
    $keys = [];
    foreach ($bag->all() as $key => $value) {
        $keys[] = $prefix.$key;
        if ($value instanceof Databag) {
          $keys = array_merge($this->getDataPaths($value, $prefix.$key.'.'), $keys);
        }
    }
    return $keys;
  }

  /**
   * Check a property path exists.
   */
  public function hasProperty($key)
  {
    try {
      $this->propertyAccessor->getValue($this->properties, $key);
      return TRUE;
    }
    catch (NoSuchIndexException $e) {
      return FALSE;
    }
  }

  abstract public function parse($data):TargetInterface;
}
