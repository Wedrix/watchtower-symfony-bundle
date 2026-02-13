<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Wedrix\WatchtowerBundle\Service\RoutesLoader;

final class RoutesLoaderTest extends TestCase
{
    public function testItRegistersPostOnlyGraphqlRoute(): void
    {
        $route = (new RoutesLoader('/graphql.json'))()
            ->get('watchtower_graphql_api');

        self::assertNotNull($route);
        self::assertSame('/graphql.json', $route->getPath());
        self::assertSame(['POST'], $route->getMethods());
    }
}
