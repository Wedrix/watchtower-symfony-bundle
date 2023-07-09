<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Controller;

use GraphQL\Error\DebugFlag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wedrix\Watchtower\Executor as WatchtowerExecutor;

final class WatchtowerController extends AbstractController
{
    /**
     * @var array<string,mixed>
     */
    private readonly array $context;
    
    /**
     * @param array<string,mixed> $context
     */
    public function __construct(
        private readonly WatchtowerExecutor $executor,
        private readonly bool $debug,
        ContainerInterface $container,
        array $context
    )
    {
        $this->context = (static function() use($context, $container): array {
            $resolvedContext = [];

            foreach ($context as $key => $serviceId) {
                $resolvedContext[$key] = $container->get($serviceId);
            }

            return $resolvedContext;
        })();
    }

    public function __invoke(
        Request $request
    ): Response
    {
        $input = \json_decode($request->getContent(), true);

        $response = new Response();

        $response->setContent(
            content: \is_string(
                $responseBody = \json_encode(
                    $this->executor
                        ->executeQuery(
                            source: $input['query'] ?? '',
                            rootValue: [],
                            contextValue: [
                                'request' => $request,
                                'response' => $response,
                                ...$this->context
                            ],
                            variableValues: $input['variables'] ?? null,
                            operationName: $input['operationName'] ?? null,
                            validationRules: null
                        )
                        ->toArray(
                            debug: $this->debug
                                ? DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE
                                : DebugFlag::NONE,
                        )
                )
            ) 
            ? $responseBody
            : throw new \Exception('Unable to encode GraphQL result')
        )
        ->headers
        ->set('Content-Type', 'application/json; charset=utf-8');

        return $response;
    }
}