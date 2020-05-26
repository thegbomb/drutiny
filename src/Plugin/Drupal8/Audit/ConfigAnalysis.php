<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Driver\DrushFormatException;
use Drutiny\Audit\AbstractAnalysis;

/**
 * Check a configuration is set correctly.
 * @Token(
 *  name = "config",
 *  type = "mixed",
 *  description = "The returned collection config.",
 * )
 */
class ConfigAnalysis extends AbstractAnalysis
{


    public function configure()
    {
         $this->addParameter(
             'collection',
             static::PARAMETER_OPTIONAL,
             'The collection the config belongs to.',
         );
        $this->addParameter(
            'expression',
            static::PARAMETER_OPTIONAL,
            'The expression language expression to evaluate.',
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
