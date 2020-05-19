<?php

namespace Drutiny\Event;

use Drutiny\Target\TargetInterface;
use Drutiny\Target\Bridge\TargetBridgeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class TargetPropertyBridgeEvent extends Event {
  public const NAME = 'target.bridge';

  protected $value;
  protected $path;
  protected $target;

  public function __construct(TargetInterface $target, $property_path, $value)
  {
    $this->path = $property_path;
    $this->value = $value;
    $this->target = $target;
  }

  public function getPropertyPath() {
    return $this->path;
  }

  public function setValue($value) {
    $this->value = $value;
  }

  public function getValue() {
    return $this->value;
  }

  public function getTarget() {
    return $this->target;
  }
}

 ?>
