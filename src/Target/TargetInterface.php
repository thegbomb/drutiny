<?php

namespace Drutiny\Target;

/**
 * Definition of a Target.
 */
interface TargetInterface
{

  /**
   * Parse the target data passed in.
   * @param $data string to parse.
   */
    public function parse(string $data, ?string $uri = null):TargetInterface;

    /**
     * Get a serviced object.
     */
    public function getService($key);

    /**
     * Return the target identifier.
     */
    public function getId():string;

    /**
     * Set target reference.
     */
    public function setTargetName(string $name):TargetInterface;

    /**
     * Get target reference name.
     */
    public function getTargetName():string;
}
