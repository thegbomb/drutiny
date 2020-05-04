<?php

namespace Drutiny\ExpressionFunction;

use Drutiny\Annotation\ExpressionSyntax;
use Drutiny\Sandbox\Sandbox;
use Composer\Semver\Comparator;

/**
 * @ExpressionSyntax(
 * name = "semver_gt",
 * usage = "semver_gt('8.1.4', '8.4.x-alpha1')",
 * description = "Use composer SemVer (semantic versioning) to evaluate if the first argument is greater than (gt) the second argument."
 * )
 */
class SemverGtExpressionFunction implements ExpressionFunctionInterface
{
    public static function compile(Sandbox $sandbox)
    {
        list($sandbox, $v1, $v2) = func_get_args();
        return sprintf('%s > %s', $v1, $v2);
    }

    public static function evaluate(Sandbox $sandbox)
    {
        list($sandbox, $v1, $v2) = func_get_args();
        return Comparator::greaterThan($v1, $v2);
    }
}
