<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Wedrix\WatchtowerBundle\DependencyInjection\WedrixWatchtowerExtension;

class WedrixWatchtowerBundle extends Bundle
{
    private ?ExtensionInterface $containerExtension = null;

    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->containerExtension === null) {
            $this->containerExtension = new WedrixWatchtowerExtension();
        }

        return $this->containerExtension;
    }
}
