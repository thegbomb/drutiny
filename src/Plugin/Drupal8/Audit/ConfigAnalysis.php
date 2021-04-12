<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Check a configuration is set correctly.
 */
class ConfigAnalysis extends AbstractAnalysis
{


    public function configure()
    {
        parent::configure();
        $this->addParameter(
           'collection',
           static::PARAMETER_OPTIONAL,
           'The collection the config belongs to.'
        );
    }

  /**
   * @inheritDoc
   */
    public function gather(Sandbox $sandbox)
    {
        $collection = $this->getParameter('collection');

        $config = $sandbox->drush([
        'format' => 'json',
        'include-overridden' => null,
        ])->configGet($collection);

        $this->set('config', $config);
    }
}
