<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Wedrix\Watchtower\Console as WatchtowerConsole;
use Wedrix\WatchtowerBundle\Command\AddPluginCommand;

final class AddPluginCommandTest extends TestCase
{
    public function testItAddsConstraintPlugins(): void
    {
        $watchtowerConsole = $this->createMock(WatchtowerConsole::class);
        $watchtowerConsole
            ->expects(self::once())
            ->method('addConstraintPlugin')
            ->with('Product');

        $tester = $this->createTester($watchtowerConsole);
        $tester->setInputs(['constraint', 'Product']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testItAddsRootConstraintPlugins(): void
    {
        $watchtowerConsole = $this->createMock(WatchtowerConsole::class);
        $watchtowerConsole
            ->expects(self::once())
            ->method('addRootConstraintPlugin');

        $tester = $this->createTester($watchtowerConsole);
        $tester->setInputs(['root_constraint']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testItAddsRootAuthorizorPlugins(): void
    {
        $watchtowerConsole = $this->createMock(WatchtowerConsole::class);
        $watchtowerConsole
            ->expects(self::once())
            ->method('addRootAuthorizorPlugin');

        $tester = $this->createTester($watchtowerConsole);
        $tester->setInputs(['root_authorizor']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testItPassesNodeTypeToFilterPlugins(): void
    {
        $watchtowerConsole = $this->createMock(WatchtowerConsole::class);
        $watchtowerConsole
            ->expects(self::once())
            ->method('addFilterPlugin')
            ->with('Customer', 'active');

        $tester = $this->createTester($watchtowerConsole);
        $tester->setInputs(['filter', 'Customer', 'active']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    /**
     * @return CommandTester<Command>
     */
    private function createTester(WatchtowerConsole $watchtowerConsole): CommandTester
    {
        $command = new AddPluginCommand($watchtowerConsole);

        $application = new Application();
        $application->add($command);

        return new CommandTester(
            $application->find('watchtower:plugins:add')
        );
    }
}
