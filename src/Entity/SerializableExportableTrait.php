<?php

namespace Drutiny\Entity;

trait SerializableExportableTrait {

  public function serialize():string
  {
    return serialize($this->export());
  }

  public function unserialize($serialized)
  {
    $this->import(unserialize($serialized));
  }

  /**
   * Export object data for serialization.
   */
  public function export()
  {
    return get_object_vars($this);
  }

  /**
   * Import data that was output from the export method.
   *
   * @param array $export The return value of the export method.
   */
  public function import(array $export)
  {
    foreach ($export as $key => $value) {
      if (!property_exists($this, $key)) {
        continue;
      }
      $this->{$key} = $value;
    }
  }
}

 ?>
