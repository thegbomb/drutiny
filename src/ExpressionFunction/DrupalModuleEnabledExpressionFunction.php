<?php

namespace Drutiny\ExpressionFunction;

use Drutiny\Annotation\ExpressionSyntax;
use Drutiny\Sandbox\Sandbox;
use Composer\Semver\Comparator;
use Doctrine\Common\Annotations\AnnotationReader;
use Drutiny\Target\DrushTargetInterface;
use Drutiny\Driver\DrushRouter;

/**
 * @ExpressionSyntax(
 * name = "drupal_module_enabled",
 * usage = "drupal_module_enabled('page_cache')",
 * description = "Returns TRUE if the module is enabled. Otherwise FALSE."
 * )
 */
class DrupalModuleEnabledExpressionFunction implements ExpressionFunctionInterface
{
    public static function compile(Sandbox $sandbox)
    {
        list($sandbox, $module, ) = func_get_args();

        $target = $sandbox->getTarget();

        if (!($target instanceof DrushTargetInterface)) {
            return '<invalid_target>';
        }

        return $module . '?enabled?';



        $metadata = $target->getMetadata();

        $parameter = str_replace('"', '', $parameter);

        $value = "<Target Unknown Parameter: $parameter. Available: " . implode(', ', array_keys($metadata)) . ">";

        if (isset($metadata[$parameter])) {
            $value = call_user_func([$target, $metadata[$parameter]]);
        }

        return $value;
    }

    public static function evaluate(Sandbox $sandbox)
    {
        list($sandbox, $module, ) = func_get_args();

        $target = $sandbox->getTarget();

        if (!($target instanceof DrushTargetInterface)) {
            return false;
        }

        $drush = DrushRouter::createFromTarget($target, ['format' => 'json']);
        $list = $drush->pmList();

        if (!isset($list[$module])) {
            return false;
        }

        if ($list[$module]['status'] != 'Enabled') {
            return false;
        }

        return true;
    }
}
