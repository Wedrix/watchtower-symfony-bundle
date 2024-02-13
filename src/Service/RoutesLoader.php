<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RoutesLoader implements RouteLoaderInterface
{
    private readonly RouteCollection $routes;

    public function __construct(
        private readonly string $configuredEndpoint
    )
    {
        $this->routes = (function(): RouteCollection {
            $routes = new RouteCollection();

            $routes->add(
                name: 'watchtower_graphql_api',
                route: new Route(
                    path: $this->configuredEndpoint,
                    methods: ['GET','POST']
                )
            );

            return $routes;
        })();
    }

    public function __invoke(): RouteCollection
    {
        return $this->routes;
    }
}