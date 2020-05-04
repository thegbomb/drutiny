<?php

namespace Drutiny\Policy;

class DependencyException extends \Exception
{
    protected $dependency;

    public function __construct(Dependency $dependency)
    {
        $this->dependency = $dependency;
        parent::__construct("Policy dependency failed: " . $dependency->getExpression());
    }

    public function getDependency()
    {
        return $this->dependency;
    }
}
