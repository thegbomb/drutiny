<?php

namespace Drutiny\Entity;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\ConfigurationInterface;

abstract class StrictEntity implements ConfigurationInterface, ExportableInterface {

    const ENTITY_NAME = 'unknown';

  protected DataBag $dataBag;
  protected Processor $processor;
  protected array $bypassPropertyValidationOnSet = [];

  public function __construct()
  {
    $this->dataBag = new DataBag();
    $this->processor = new Processor();
  }

  abstract public function getConfigTreeBuilder();

  /**
   * Make properties read-only attributes of object.
   */
  public function __get($property)
  {
    if (!isset($this->dataBag->{$property})) {
      return $this->validatePropertyData($property, null);
    }
    return $this->dataBag->get($property);
  }

  public function __isset($property)
  {
    return isset($this->dataBag->{$property});
  }

  /**
   * Set profile value according to the profile schema.
   */
  public function __set($property, $value)
  {
    $this->setPropertyData($property, $this->validatePropertyData($property, $value, $this->bypassPropertyValidationOnSet));
  }

  /**
   * Validate a property on the entity.
   */
  protected function validatePropertyData(string $property, $value, $property_bypass = [])
  {
    try {
        $properties = $this->getConfigTreeBuilder()->buildTree()->getChildren();
        if (!isset($properties[$property])) {
          throw new InvalidConfigurationException(get_called_class()::ENTITY_NAME . " '$property' does not exist.");
        }

        $config = $properties[$property];
        $config->normalize($value);
        return $config->finalize($value);
    }
    catch (InvalidConfigurationException $e) {
        if (($value === null) && $config->hasDefaultValue()) {
          return $config->getDefaultValue();
        }
        if (in_array($property, $property_bypass)) {
          return $value;
        }
        throw new InvalidConfigurationException(get_called_class()::ENTITY_NAME . " property '$property' configuration invalid: " . $e->getMessage());
    }
  }

  protected function validateAllPropertyData():bool
  {
    try {
      $data = $this->processor->processConfiguration(
          $this,
          [get_called_class()::ENTITY_NAME => $this->dataBag->all()]
      );
    }
    catch (InvalidConfigurationException $e) {
        throw new InvalidConfigurationException(get_called_class()::ENTITY_NAME . " configuration invalid: " . $e->getMessage());
    }
    return true;
  }

  /**
   * Set the property value. This should be overridden by an extending class.
   */
  protected function setPropertyData($property, $value)
  {
    $this->dataBag->set($property, $value);
    return $this;
  }

  /**
   * Export contents of the databag.
   */
  public function export()
  {
      return $this->dataBag->export();
  }
}
