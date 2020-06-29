<?php

namespace Drutiny\Target;

use Drutiny\Entity\DataBag;
use Drutiny\Entity\EventDispatchedDataBag;
use Drutiny\Entity\Exception\DataNotFoundException;
use Drutiny\Target\Service\ExecutionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Psr\Log\LoggerInterface;

/**
 * Basic function of a Target.
 */
abstract class Target implements \ArrayAccess
{

  /* @var PropertyAccess */
  protected $propertyAccessor;
  protected $properties;
  protected $local;
  protected $logger;
  protected $dispatcher;

  public function __construct(ExecutionInterface $local, LoggerInterface $logger, EventDispatchedDataBag $databag)
  {
    $this->logger = $logger;
    $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
      ->enableExceptionOnInvalidIndex()
      ->getPropertyAccessor();

    $this->properties = $databag->setObject($this);

    $local->setTarget($this);

    $this['service.local'] = $local;
    $this['service.exec'] = $local;
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
  public function getService($key)
  {
    return $this->getProperty('service.'.$key);
  }

  /**
   * Allow the execution service to change depending on the target environment.
   */
  public function setExecService(ExecutionInterface $service)
  {
    return $this->setProperty('service.exec', $service);
  }

  /**
   * Set a property.
   */
  public function setProperty($key, $value)
  {
    $this->confirmPropertyPath($key);
    $this->propertyAccessor->setValue($this->properties, $key, $value);
    return $this;
  }

  /**
   * Ensure the property pathway exists.
   */
  protected function confirmPropertyPath($path)
  {
      // Handle top level properties.
      if (strpos($path, '.') === FALSE) {
        return $this;
      }

      $bits = explode('.', $path);
      $total_bits = count($bits);
      $new_paths = [];
      do {
          $pathway = implode('.', $bits);

          if (empty($pathway)) {
              break;
          }

          // If the pathway doesn't exist yet, create it as a new DataBag.
          if ($this->hasProperty($pathway)) {
              break;
          }

          // If the parent is a DataBag then the pathway is settable.
          if ($total_bits == count($bits) && $this->getParentProperty($pathway) instanceof DataBag) {
              break;
          }
          $new_paths[] = $pathway;
      }
      while (array_pop($bits));

      foreach (array_reverse($new_paths) as $pathway) {
        $this->setProperty($pathway, $this->properties->create()->setEventPrefix($pathway));
      }
      return $this;
  }

  /**
   * Find the parent value.
   */
  private function getParentProperty($path)
  {
      if (strpos($path, '.') === FALSE) {
          return false;
      }
      $bits = explode('.', $path);
      array_pop($bits);
      $path = implode('.', $bits);
      return $this->hasProperty($path) ? $this->getProperty($path) : false;
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

  /**
   * Traverse DataBags to obtain a list of property pathways.
   */
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
      return true;
    }
    catch (NoSuchIndexException $e) {
      return false;
    }
    catch (DataNotFoundException $e) {
      return false;
    }
  }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            throw new \Exception(__CLASS__ . ' does not support numeric indexes as properties.');
        }
        return $this->setProperty($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset) {
        return $this->hasProperty($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset) {
        throw new \Exception("Cannot unset $offset. Properties cannot be removed. Please set to null instead.");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset) {
        return $this->hasProperty($offset) ? $this->getProperty($offset) : null;
    }

    abstract public function parse($data):TargetInterface;
}
