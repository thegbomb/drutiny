<?php

namespace Drutiny\ExpressionLanguage\Func;

use Composer\Semver\Comparator;

class SemverGte extends ExpressionFunction implements FunctionInterface
{
    public function getName()
    {
        return 'semver_gte';
    }

    public function getCompiler()
    {
        return function ($v1, $v2) {
          return sprintf('%s >= %s', $v1, $v2);
        };
    }

    public function getEvaluator()
    {
        return function ($args, $v1, $v2) {
            return Comparator::greaterThanOrEqualTo($v1, $v2);
        };
    }
}
