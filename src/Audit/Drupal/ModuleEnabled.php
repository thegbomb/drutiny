<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\RemediableInterface;

/**
 * Generic module is enabled check.
 *
 */
class ModuleEnabled extends Audit implements RemediableInterface
{

    public function configure()
    {
           $this->addParameter(
               'module',
               static::PARAMETER_OPTIONAL,
               'The module to check is enabled.',
           );
    }


  /**
   *
   */
    public function audit(Sandbox $sandbox)
    {

        $module = $this->getParameter('module');
        $info = $sandbox->drush(['format' => 'json'])->pmList();

        if (!isset($info[$module])) {
            return false;
        }

        $status = strtolower($info[$module]['status']);

        return ($status == 'enabled');
    }

    public function remediate(Sandbox $sandbox)
    {
        $module = $this->getParameter('module');
        $sandbox->drush()->en($module, '-y');
        return $this->audit($sandbox);
    }
}
