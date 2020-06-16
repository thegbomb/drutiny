<?php

namespace Drutiny\Target;

use Drutiny\Annotation\Metadata;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Definition of a Target.
 */
interface TargetInterface
{

  /**
   * Parse the target data passed in.
   * @param $data string to parse.
   */
    public function parse($data):TargetInterface;

    /**
     * Get a serviced object.
     */
    public function getService($key);
}
