<?php

namespace Drutiny\ExpressionLanguage\Func;

use Drutiny\Target\TargetInterface;

class Target extends ExpressionFunction implements ContainerDependentFunctionInterface
{
    private $target;

    public function __construct(TargetInterface $target)
    {
      $this->target = $target;
    }

    public function getName()
    {
        return 'target';
    }

    public function getCompiler()
    {
        return function ($property) {
            return $this->target->getProperty($property);
        };
    }

    public function getEvaluator()
    {
        return function ($args, string $property) {
            return $this->target->getProperty($property);
        };
    }
}
