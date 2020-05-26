<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\RemediableInterface;

/**
 * Generic module is disabled check.
 */
class ModuleDisabled extends Audit implements RemediableInterface
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

        try {
            $info = $sandbox->drush(['format' => 'json'])->pmList();
        } catch (\Exception $e) {
            return strpos($e->getOutput(), $module . ' was not found.') !== false;
        }

        if (!isset($info[$module])) {
            return true;
        }

        $status = strtolower($info[$module]['status']);

        return ($status != 'enabled');
    }

    public function remediate(Sandbox $sandbox)
    {
        $module = $this->getParameter('module');
        $sandbox->drush()->pmUninstall($module, '-y');
        return $this->audit($sandbox);
    }
}
