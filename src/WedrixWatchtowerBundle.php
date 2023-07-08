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
                ->scalarNode('schema_cache_directory')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->booleanNode('cache_schema')
                    ->isRequired()
                ->end()
                ->booleanNode('debug')
                    ->isRequired()
                ->end()
                ->arrayNode('context')
                    ->cannotBeEmpty()
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
            ->arg(0, $config['endpoint']);

        $containerConfigurator->services()
            ->get('Wedrix\WatchtowerBundle\Controller\WatchtowerController')
            ->arg(2, $config['context'])
            ->arg(3, $config['debug']);

        $containerConfigurator->services()
            ->set('Wedrix\Watchtower\Executor')
            ->arg(0, '@doctrine.orm.entity_manager')
            ->arg(1, $config['schema_file'])
            ->arg(2, $config['plugins_directory'])
            ->arg(3, $config['scalar_type_definitions_directory'])
            ->arg(4, $config['cache_schema'])
            ->arg(5, $config['schema_cache_directory']);

        $containerConfigurator->services()
            ->set('Wedrix\Watchtower\Console')
            ->arg(0, '@doctrine.orm.entity_manager')
            ->arg(1, $config['schema_file'])
            ->arg(2, $config['plugins_directory'])
            ->arg(3, $config['scalar_type_definitions_directory'])
            ->arg(4, $config['schema_cache_directory']);
    }
}