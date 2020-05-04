<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Token;

/**
 * Drush Status Information
 * @Token(
 *  name = "status",
 *  type = "array",
 *  description = "The status object returned by drush."
 * )
 */
class StatusInformation extends Audit
{

  /**
   *
   */
    public function audit(Sandbox $sandbox)
    {
        $stat = $sandbox->drush(['format' => 'json'])->status();
        $sandbox->setParameter('status', $stat);

        return Audit::NOTICE;
    }
}
