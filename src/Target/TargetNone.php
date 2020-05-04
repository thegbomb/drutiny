<?php

namespace Drutiny\Target;

/**
 * @Drutiny\Annotation\Target(
 *  name = "none"
 * )
 */
class TargetNone extends Target
{

  /**
   * Set a default URI.
   */
    public function parse($target_data)
    {
        $this->setUri('http://default/');
        return $this;
    }
}
