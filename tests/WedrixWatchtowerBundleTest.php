<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Wedrix\WatchtowerBundle\DependencyInjection\WedrixWatchtowerExtension;
use Wedrix\WatchtowerBundle\WedrixWatchtowerBundle;

final class WedrixWatchtowerBundleTest extends TestCase
{
    public function testItProvidesItsCustomContainerExtensionAlias(): void
    {
        $bundle = new WedrixWatchtowerBundle();

        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(WedrixWatchtowerExtension::class, $extension);
        self::assertSame('wedrix_watchtower_bundle', $extension->getAlias());
        self::assertSame($extension, $bundle->getContainerExtension());
    }
}
