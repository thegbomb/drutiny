<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;

/**
 * Generic module is disabled check.
 */
class NoExperimentalCore extends Audit
{

  /**
   *
   */
    public function audit(Sandbox $sandbox)
    {

        $info = $sandbox->drush([
        'format' => 'json',
        'status' => 'Enabled',
        'core',
        ])->pmList();

        $info = array_filter($info, function ($package) {
            return strpos(strtolower($package['package']), 'experimental') !== false;
        });

        if (empty($info)) {
            return true;
        }

        $this->set('modules', array_values($info));
        return false;
    }
}
