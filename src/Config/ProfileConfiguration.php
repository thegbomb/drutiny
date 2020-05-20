<?php

namespace Drutiny\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ProfileConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('profile');
        $treeBuilder->getRootNode()
          ->children()
            ->scalarNode('title')
              ->info('The human readable name of the profile.')
              ->isRequired()
              ->cannotBeEmpty()
              ->end()
            ->scalarNode('name')
              ->info('The machine-name of the profile.')
              ->isRequired()
              ->cannotBeEmpty()
              ->end()
            ->scalarNode('description')
              ->info('A description why the profile is valuable to use.')
              ->defaultValue('')
              ->end()
            ->scalarNode('language')
              ->defaultValue('en')
              ->info('Language code')
              ->end()

            // Configuration
            ->scalarNode('uuid')
              ->info('Unique identifier such as a URL.')
              ->isRequired()
              ->cannotBeEmpty()
              ->end()
            ->arrayNode('policies')
              ->info('A list of policies that this profile runs.')
              ->useAttributeAsKey('name')
                ->arrayPrototype()
                  ->children()
                    ->scalarNode('severity')
                      ->info('The severity override to use for the policy in this profile.')
                      ->defaultValue('normal')
                      ->end()
                    ->arrayNode('parameters')
                      ->info('The parameter overrides to use for the policy in this profile.')
                      ->variablePrototype()->end()
                      ->end()
                  ->end()
                ->end()
              ->end()
            ->arrayNode('include')
              //->useAttributeAsKey('name')
            ->end()
            ->arrayNode('excluded_policies')
            ->end()
            ->arrayNode('format')
              ->useAttributeAsKey('name')
              ->info('Configuration for a given format.')
                ->arrayPrototype()
                  ->children()
                    ->scalarNode('template')->end()
                    ->arrayNode('content')->end()
                  ->end()
                ->end()
              ->end();

        return $treeBuilder;
    }
}
