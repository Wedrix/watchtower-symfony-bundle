<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wedrix_watchtower_bundle');

        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('endpoint')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('schema_file')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('plugins_directory')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('scalar_type_definitions_directory')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('cache_directory')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->booleanNode('optimize')
                    ->isRequired()
                ->end()
                ->booleanNode('debug')
                    ->isRequired()
                ->end()
                ->variableNode('context')
                    ->defaultValue([])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
