<?php

declare(strict_types=1);

namespace Wedrix\Watchtower\ResolverPlugin;

use Wedrix\Watchtower\Resolver\Node;

function resolve_query_inspect_field(Node $node): string
{
    return ($node->context()['probe']->callback)($node);
}
