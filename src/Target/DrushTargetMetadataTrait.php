<?php

namespace Drutiny\Target;

use Drutiny\Driver\DrushRouter;

trait DrushTargetMetadataTrait
{

  /**
   * {@inheritdoc}
   */
    public function metadataDrushStatus()
    {
        $drush = DrushRouter::createFromTarget($this, ['format' => 'json']);
        return $drush->status();
    }

  /**
   * {@inheritdoc}
   */
    public function metadataPhpVersion()
    {
        $drush = DrushRouter::createFromTarget($this);
        return $drush->evaluate(function () {
            return phpversion();
        });
    }
}
