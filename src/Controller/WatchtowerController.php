<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Controller;

use GraphQL\Error\DebugFlag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wedrix\Watchtower\Executor as WatchtowerExecutor;

final class WatchtowerController extends AbstractController
{
    /**
     * @var array<string,mixed>
     */
    public function index(
        Request $request,
        WatchtowerExecutor $executor,
        array $context,
        bool $debug
    ): Response
    {
        $input = \json_decode($request->getContent(), true);

        $response = new Response();

        $response->setContent(
            content: \is_string(
                $responseBody = \json_encode(
                    $executor->executeQuery(
                        source: $input['query'] ?? '',
                        rootValue: [],
                        contextValue: [
                            'request' => $request, 
                            'response' => $response,
                            ...$context
                        ],
                        variableValues: $input['variables'] ?? null,
                        operationName: $input['operationName'] ?? null,
                        validationRules: null
                    )
                    ->toArray(
                        debug: $debug
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