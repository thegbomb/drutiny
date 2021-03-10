<?php

namespace Drutiny\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Drutiny\Entity\PolicyOverride;

trait PolicyOverrideConfigurationTrait
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(PolicyOverride::ENTITY_NAME);
        $treeBuilder->getRootNode()
          ->children()
            ->scalarNode('name')
              ->info('The machine-name of the policy.')
              ->isRequired()
              ->cannotBeEmpty()
              ->end()

            // Classification
            ->enumNode('severity')
              ->info('What severity level the policy is rated at.')
              ->values(['low', 'normal', 'high', 'critical'])
              ->end()

            // Classification
            ->integerNode('weight')
              ->info('Weighting used to order a policy in a list.')
              ->defaultValue(0)
              ->treatNullLike(0)
              ->end()

            // Working variables.
            ->arrayNode('parameters')
              ->info('Parameters are values that maybe used to configure an audit for use with the Policy.')
              ->variablePrototype()->end()
              ->end();

        return $treeBuilder;
    }
}
