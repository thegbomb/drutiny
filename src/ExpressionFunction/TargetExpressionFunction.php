<?php

namespace Drutiny\ExpressionFunction;

use Drutiny\Annotation\ExpressionSyntax;
use Drutiny\Sandbox\Sandbox;
use Composer\Semver\Comparator;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * @ExpressionSyntax(
 * name = "target",
 * usage = "target('php_version')",
 * description = "Obtain a variable from the assessed target. See `target:metadata` for all available variables."
 * )
 */
class TargetExpressionFunction implements ExpressionFunctionInterface
{
    public static function compile(Sandbox $sandbox)
    {
        list($sandbox, $parameter, ) = func_get_args();

        $target = $sandbox->getTarget();
        $metadata = $target->getMetadata();

        $parameter = str_replace('"', '', $parameter);

        $value = "<Target Unknown Parameter: $parameter. Available: " . implode(', ', array_keys($metadata)) . ">";

        if (isset($metadata[$parameter])) {
            $value = call_user_func([$target, $metadata[$parameter]]);
        }

        return is_string($value) ? $value : $parameter;
    }

    public static function evaluate(Sandbox $sandbox)
    {
        list($sandbox, $parameter, ) = func_get_args();

        $target = $sandbox->getTarget();
        $metadata = $target->getMetadata();

        $value = "";

        if (isset($metadata[$parameter])) {
            $value = call_user_func([$target, $metadata[$parameter]]);
        }

        return $value;
    }
}
