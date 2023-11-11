<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class WedrixWatchtowerBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
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
                ->end()
            ->end();
    }

    public function loadExtension(
        array $config, 
        ContainerConfigurator $containerConfigurator, 
        ContainerBuilder $containerBuilder
    ): void
    {
        $containerConfigurator->import('../config/services.yaml');

        $containerConfigurator->services()
            ->get('Wedrix\WatchtowerBundle\Service\RoutesLoader')
            ->arg('$configuredEndpoint', $config['endpoint']);

        $containerConfigurator->services()
            ->get('Wedrix\WatchtowerBundle\Controller\WatchtowerController')
            ->arg('$context', $config['context'] ?? [])
            ->arg('$debug', $config['debug']);

        $containerConfigurator->services()
            ->set('Wedrix\Watchtower\Executor')
            ->autowire()
            ->arg('$schemaFile', $config['schema_file'])
            ->arg('$pluginsDirectory', $config['plugins_directory'])
            ->arg('$scalarTypeDefinitionsDirectory', $config['scalar_type_definitions_directory'])
            ->arg('$optimize', $config['optimize'])
            ->arg('$cacheDirectory', $config['cache_directory']);

        $containerConfigurator->services()
            ->set('Wedrix\Watchtower\Console')
            ->autowire()
            ->arg('$schemaFile', $config['schema_file'])
            ->arg('$pluginsDirectory', $config['plugins_directory'])
            ->arg('$scalarTypeDefinitionsDirectory', $config['scalar_type_definitions_directory'])
            ->arg('$cacheDirectory', $config['cache_directory']);
    }
}