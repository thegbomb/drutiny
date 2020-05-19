<?php

namespace Drutiny\ExpressionLanguage\Func;

use Drutiny\Target\TargetInterface;

class DrupalModuleEnabled extends ExpressionFunction implements ContainerDependentFunctionInterface
{
    private $target;

    public function __construct(TargetInterface $target)
    {
      $this->target = $target;
    }

    public function getName()
    {
        return 'drupal_module_enabled';
    }

    public function getCompiler()
    {
        return function ($module_name) {
            return sprintf("(%s is enabled)", $module_name);
        };
    }

    public function getEvaluator()
    {
        return function ($args, $module_name) {
          $list = $this->target->getBridge('drush')
            ->pmList(['format' => 'json'])
            ->run(function($output) {
              return json_decode($output, TRUE);
            });

          if (!isset($list[$module_name])) {
            return false;
          }
          $status = strtolower($list[$module_name]['status']);
          return $status == 'enabled';
        };
    }
}
