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
     * Get a bridged object.
     */
    public function getBridge($key);
}
