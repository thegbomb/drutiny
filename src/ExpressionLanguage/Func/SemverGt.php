<?php

namespace Drutiny\ExpressionLanguage\Func;

use Composer\Semver\Comparator;

class SemverGt extends ExpressionFunction implements FunctionInterface
{
    public function getName()
    {
        return 'semver_gt';
    }

    public function getCompiler()
    {
        return function ($v1, $v2) {
          return sprintf('%s > %s', $v1, $v2);
        };
    }

    public function getEvaluator()
    {
        return function ($args, $v1, $v2) {
            return Comparator::greaterThan($v1, $v2);
        };
    }
}
