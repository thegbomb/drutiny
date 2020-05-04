<?php

namespace Drutiny\DomainList;

/**
 * Abstract domain source provider
 */
abstract class AbstractDomainList {

  private $options;

  public function __construct()
  {
    $this->configure();
  }

  public function configure()
  {
  }

  protected function addOption($option, $description)
  {
    $this->options[$option] = $description;
    return $this;
  }

  public function getOptionsDefinitions() {
    return $this->options;
  }

  abstract public function getDomains(array $options = []);
}
