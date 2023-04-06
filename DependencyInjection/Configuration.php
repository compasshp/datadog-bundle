<?php

namespace Compass\DatadogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('compass_datadog');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('trace')
                    ->children()
                        ->booleanNode('enabled')->defaultValue(false)->end()
                        ->scalarNode('user_entity')->end()
                        ->arrayNode('user_properties')
                            ->defaultValue([])
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('appsec')
                    ->children()
                    ->booleanNode('enabled')->defaultValue(false)->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
