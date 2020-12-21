<?php

namespace Drutiny\Policy;

class DependencyException extends \Exception
{
    protected $dependency;

    public function __construct(Dependency $dependency)
    {
        $this->dependency = $dependency;
        parent::__construct(sprintf("Policy dependency failed: %s (%s).",$dependency->getDescription(), $dependency->getExpression()));
    }

    public function getDependency()
    {
        return $this->dependency;
    }
}
