<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Wedrix\WatchtowerBundle\Controller\WatchtowerController;
use Wedrix\WatchtowerBundle\Service\RoutesLoader;

class WedrixWatchtowerExtension extends Extension
{
    /**
     * @param array<int,array<string,mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');

        $container
            ->getDefinition(RoutesLoader::class)
            ->setArgument('$configuredEndpoint', $config['endpoint']);

        $container
            ->getDefinition(WatchtowerController::class)
            ->setArgument('$context', $config['context'] ?? [])
            ->setArgument('$debug', $config['debug']);

        $container
            ->register('Wedrix\Watchtower\Executor')
            ->setClass('Wedrix\Watchtower\Executor')
            ->setFactory('Wedrix\Watchtower\Executor')
            ->setArguments([
                new Reference('doctrine.orm.entity_manager'),
                $config['schema_file'],
                $config['plugins_directory'],
                $config['scalar_type_definitions_directory'],
                $config['cache_directory'],
                $config['optimize'],
            ]);

        $container
            ->register('Wedrix\Watchtower\Console')
            ->setClass('Wedrix\Watchtower\Console')
            ->setFactory('Wedrix\Watchtower\Console')
            ->setArguments([
                new Reference('doctrine.orm.entity_manager'),
                \dirname($config['schema_file']),
                \pathinfo($config['schema_file'], \PATHINFO_BASENAME),
                $config['plugins_directory'],
                $config['scalar_type_definitions_directory'],
                $config['cache_directory'],
            ]);
    }

    public function getAlias(): string
    {
        return 'wedrix_watchtower_bundle';
    }
}
