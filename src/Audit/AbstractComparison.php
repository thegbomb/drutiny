<?php

namespace Drutiny\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;

/**
 * Comparatively evaluate two values.
 */
abstract class AbstractComparison extends Audit
{

    protected function compare($reading, $value, Sandbox $sandbox)
    {
        $comp_type = $sandbox->getParameter('comp_type', '==');
        $this->logger->warning(static::class.' extends '.__CLASS__.' and is deprecated. Please use Drutiny\Audit\AbstractAnalysis instead.');

        $params = [
          'reading' => $reading,
          'value' => $value,
        ];
        $expression = strtr("reading $comp_type value", [
          'lt' => '<',
          'gt' => '>',
          'lte' => '<=',
          'gte' => '>=',
          'ne' => '!=',
          'nie' => '!==',
          'identical' => '===',
          'equal' => '==',
          '~' => 'matches'
        ]);
        $this->logger->debug(static::class . ':EXPRESSION: ' . $expression);
        return $this->container->get('expression_language')->evaluate($expression, $params);
    }
}
