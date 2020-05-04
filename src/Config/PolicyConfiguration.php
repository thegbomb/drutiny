<?php

namespace Drutiny\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class PolicyConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('policy');
        $treeBuilder->getRootNode()
          ->children()
            ->scalarNode('title')
              ->info('The human readable name of the policy.')
              ->isRequired()
              ->cannotBeEmpty()
              ->end()
            ->scalarNode('name')
              ->info('The machine-name of the policy.')
              ->isRequired()
              ->cannotBeEmpty()
              ->end()
            ->scalarNode('class')
              ->info('A PHP Audit class to pass the policy to be assessed.')
              ->isRequired()
              ->cannotBeEmpty()
              ->end()
            ->scalarNode('description')
              ->info('A description why the policy is valuable.')
              ->isRequired()
              ->cannotBeEmpty()
              ->end()
            ->scalarNode('language')
              ->defaultValue('en')
              ->info('Language code')
              ->end()

            // Classification
            ->enumNode('type')
              ->info('What type of policy this is. Audit types return a pass/fail result while data types return only data.')
              ->values(['audit', 'data'])
              ->defaultValue('audit')
              ->end()
            ->arrayNode('tags')
              ->info('A set of tags to categorize a policy.')
              ->scalarPrototype()->end()
              ->end()
            ->enumNode('severity')
              ->info('What severity level the policy is rated at.')
              ->values(['low', 'normal', 'high', 'critical'])
              ->defaultValue('normal')
              ->end()

            // Working variables.
            ->arrayNode('parameters')
              ->info('Parameters are values that maybe used to configure an audit for use with the Policy.')
              ->variablePrototype()->end()
              ->end()

            // Messaging
            ->scalarNode('remediation')
              ->info('Content to communicate how to remediate a policy failure.')
              ->end()
            ->scalarNode('failure')
              ->info('Content to communicate a policy failure.')
              ->end()
            ->scalarNode('success')
              ->info('Content to communicate a policy success.')
              ->end()
            ->scalarNode('warning')
              ->info('Content to communicate a policy warning (in a success).')
              ->end()

            // Configuration
            ->scalarNode('uuid')
              ->info('Unique identifier such as a URL.')
              ->isRequired()
              ->cannotBeEmpty()
              ->end()
            ->arrayNode('depends')
              ->info('A list of other policies that this policy depends on.')
                ->arrayPrototype()
                  ->children()
                    ->enumNode('on_fail')
                      ->values(['fail', 'omit', 'error', 'report_only'])
                      ->defaultValue('report_only')
                      ->end()
                    ->scalarNode('expression')
                      ->isRequired()
                      ->end()
                  ->end()
                ->end()
              ->end()
            ->arrayNode('compatibility')
              ->info('A list of checks to check for compatitbility with assessable target.')
              ->end()
            ->arrayNode('chart')
              ->info('Configuration for any charts used in the policy messaging.')
                ->arrayPrototype()
                  ->children()
                    ->scalarNode('title')->end()
                    ->enumNode('type')
                    ->values(['bar', 'line', 'pie', 'doughnut'])
                    ->defaultValue('bar')
                    ->end()
                    ->booleanNode('hide-table')->defaultValue(false)->end()
                    ->booleanNode('stacked')->defaultValue(false)->end()
                    ->arrayNode('series')->end()
                    ->arrayNode('series-labels')->end()
                    ->arrayNode('labels')->end()
                  ->end()
                ->end()
              ->end();

        return $treeBuilder;
    }
}
