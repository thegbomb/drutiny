<?php

namespace Drutiny\Target;

use Drutiny\Event\TargetPropertyBridgeEvent;
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
  protected $knownPropertyPaths = [];
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
    $this->properties = $this->createPropertyInstance();
    $local->setTarget($this);
    $this->createProperty('bridge')
      ->setProperty('bridge.local', $local)
      ->setProperty('bridge.exec', $local);
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri)
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
    return $this->setProperty($key, $this->createPropertyInstance());
  }

  private function createPropertyInstance()
  {
    return new class {
      public $value;

      public function __set($key, $value)
      {
        $this->value = $this->value ?? new \stdClass;
        $this->value->{$key} = $value;
        return $value;
      }

      public function __get($key)
      {
        if (!isset($this->value->{$key})) {
          throw new NoSuchIndexException("$key doesn't exist.");
        }
        return $this->value->{$key};
      }
    };
  }

  /**
   * Set a property.
   */
  protected function setProperty($key, $value)
  {
    // Allow property bridges to change the value.
    $event = new TargetPropertyBridgeEvent($this, $key, $value);
    $event_name = 'target.property.'.$key;
    $this->logger->debug("Firing event '$event_name' from ".static::class);
    $this->dispatcher->dispatch($event, $event_name);
    $value = $event->getValue();
    $this->propertyAccessor->setValue($this->properties, $key, $value);
    $this->logger->debug("Setting ".static::class." property '$key' with value of type " . gettype($value));

    if (!in_array($key, $this->knownPropertyPaths)) {
      $this->knownPropertyPaths[] = $key;
    }

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
    sort($this->knownPropertyPaths);
    return $this->knownPropertyPaths;
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
