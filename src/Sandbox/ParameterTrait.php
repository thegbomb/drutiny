<?php

namespace Drutiny\Sandbox;

/**
 *
 */
trait ParameterTrait
{

  /**
   * @var array
   */
    protected $params = [];

  /**
   * Expose parameters to the check.
   */
    public function getParameter($key, $default_value = null)
    {
        return $this->audit->getParameter($key) ?? $default_value;
    }

  /**
   *
   */
    public function setParameter($key, $value)
    {
        $this->audit->setParameter($key, $value);
        return $this;
    }

  /**
   *
   */
    public function setParameters(array $params)
    {
        $this->audit->get('parameters')->add($params);
        return $this;
    }
}
