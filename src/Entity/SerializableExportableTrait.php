<?php

namespace Drutiny\Entity;

trait SerializableExportableTrait {

  public function serialize():string
  {
    return serialize($this->export());
  }

  public function unserialize(string $serialized)
  {
    $this->import(unserialize($serialized));
  }

  abstract protected function export();
  abstract protected function import($export);
}

 ?>
