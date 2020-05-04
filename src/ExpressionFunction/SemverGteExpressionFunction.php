<?php

namespace Drutiny\ExpressionFunction;

use Drutiny\Annotation\ExpressionSyntax;
use Drutiny\Sandbox\Sandbox;
use Composer\Semver\Comparator;

/**
 * @ExpressionSyntax(
 * name = "semver_gte",
 * usage = "semver_gte('8.1.4', '8.4.x-alpha1')",
 * description = "Use composer SemVer (semantic versioning) to evaluate if the first argument is greater or equal (gte) the second argument."
 * )
 */
class SemverGteExpressionFunction implements ExpressionFunctionInterface
{

  /**
   * {@inheritdoc}
   */
    public static function compile(Sandbox $sandbox)
    {
        list($sandbox, $v1, $v2) = func_get_args();
        return sprintf('%s >= %s', $v1, $v2);
    }

  /**
   * {@inheritdoc}
   */
    public static function evaluate(Sandbox $sandbox)
    {
        list($sandbox, $v1, $v2) = func_get_args();
        return Comparator::greaterThanOrEqualTo($v1, $v2);
    }
}
