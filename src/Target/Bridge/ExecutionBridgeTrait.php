<?php

namespace Drutiny\Target\Bridge;

trait ExecutionBridgeTrait {
  protected $execBridge;

  public function getExecBridge()
  {
    return $this->execBridge;
  }

  public function setExecBridge(ExecutionInterface $bridge)
  {
    $this->execBridge = $bridge;
    return $this;
  }
}
