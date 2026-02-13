<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Wedrix\WatchtowerBundle\Controller\WatchtowerController;
use Wedrix\WatchtowerBundle\DependencyInjection\WedrixWatchtowerExtension;
use Wedrix\WatchtowerBundle\Service\RoutesLoader;

final class WedrixWatchtowerExtensionTest extends TestCase
{
    public function testItConfiguresFactoryServicesAndServiceArguments(): void
    {
        $container = new ContainerBuilder();
        $extension = new WedrixWatchtowerExtension();

        $extension->load([[
            'endpoint' => '/graphql.json',
            'schema_file' => '/var/app/graphql/schema.graphql',
            'plugins_directory' => '/var/app/graphql/plugins',
            'scalar_type_definitions_directory' => '/var/app/graphql/scalars',
            'cache_directory' => '/var/app/cache/watchtower',
            'optimize' => true,
            'debug' => false,
            'context' => [
                'entity_manager' => 'doctrine.orm.entity_manager',
                'logger' => 'logger',
            ],
        ]], $container);

        self::assertSame(
            '/graphql.json',
            $container->getDefinition(RoutesLoader::class)->getArgument('$configuredEndpoint')
        );

        self::assertSame(
            [
                'entity_manager' => 'doctrine.orm.entity_manager',
                'logger' => 'logger',
            ],
            $container->getDefinition(WatchtowerController::class)->getArgument('$context')
        );
        self::assertFalse(
            $container->getDefinition(WatchtowerController::class)->getArgument('$debug')
        );

        $executorDefinition = $container->getDefinition('Wedrix\Watchtower\Executor');
        self::assertSame('Wedrix\Watchtower\Executor', $executorDefinition->getClass());
        self::assertSame('Wedrix\Watchtower\Executor', $executorDefinition->getFactory());
        self::assertEquals(
            [
                new Reference('doctrine.orm.entity_manager'),
                '/var/app/graphql/schema.graphql',
                '/var/app/graphql/plugins',
                '/var/app/graphql/scalars',
                '/var/app/cache/watchtower',
                true,
            ],
            $executorDefinition->getArguments()
        );

        $consoleDefinition = $container->getDefinition('Wedrix\Watchtower\Console');
        self::assertSame('Wedrix\Watchtower\Console', $consoleDefinition->getClass());
        self::assertSame('Wedrix\Watchtower\Console', $consoleDefinition->getFactory());
        self::assertEquals(
            [
                new Reference('doctrine.orm.entity_manager'),
                '/var/app/graphql',
                'schema.graphql',
                '/var/app/graphql/plugins',
                '/var/app/graphql/scalars',
                '/var/app/cache/watchtower',
            ],
            $consoleDefinition->getArguments()
        );
    }

    public function testItDefaultsContextToEmptyArray(): void
    {
        $container = new ContainerBuilder();
        $extension = new WedrixWatchtowerExtension();

        $extension->load([[
            'endpoint' => '/graphql.json',
            'schema_file' => '/var/app/graphql/schema.graphql',
            'plugins_directory' => '/var/app/graphql/plugins',
            'scalar_type_definitions_directory' => '/var/app/graphql/scalars',
            'cache_directory' => '/var/app/cache/watchtower',
            'optimize' => false,
            'debug' => true,
        ]], $container);

        self::assertSame(
            [],
            $container->getDefinition(WatchtowerController::class)->getArgument('$context')
        );
    }
}
