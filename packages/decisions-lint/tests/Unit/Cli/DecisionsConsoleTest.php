<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use DecisionsLint\Cli\Command\CheckCommand;
use DecisionsLint\Cli\Command\InitCommand;
use DecisionsLint\Cli\Command\LintCommand;
use DecisionsLint\Cli\Command\PlanCommand;
use DecisionsLint\Cli\Command\RulesCommand;
use DecisionsLint\Cli\Command\ValidateCommand;
use DecisionsLint\Cli\DecisionsConsole;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \DecisionsLint\Cli\DecisionsConsole
 * @uses \DecisionsLint\Cli\Command\CheckCommand
 * @uses \DecisionsLint\Cli\Command\InitCommand
 * @uses \DecisionsLint\Cli\Command\LintCommand
 * @uses \DecisionsLint\Cli\Command\PlanCommand
 * @uses \DecisionsLint\Cli\Command\RulesCommand
 * @uses \DecisionsLint\Cli\Command\ValidateCommand
 */
#[CoversClass(DecisionsConsole::class)]
#[UsesClass(CheckCommand::class)]
#[UsesClass(InitCommand::class)]
#[UsesClass(LintCommand::class)]
#[UsesClass(PlanCommand::class)]
#[UsesClass(RulesCommand::class)]
#[UsesClass(ValidateCommand::class)]
final class DecisionsConsoleTest extends TestCase
{
    public function testGetHelpExplainsTheDefaultCommand(): void
    {
        $console = new DecisionsConsole('/project');
        self::assertStringContainsString('Running decisions-lint without a command runs check.', $console->getHelp());
        self::assertSame('decisions-lint', $console->getName());
        self::assertSame(DecisionsConsole::VERSION, $console->getVersion());
        self::assertTrue($console->has('check'));
        self::assertTrue($console->has('plan'));
        self::assertTrue($console->has('rules'));
        self::assertTrue($console->has('validate'));
        self::assertTrue($console->has('init'));
    }

    public function testDoRunListsCommandsForBareHelpAndRejectsUnknownCommands(): void
    {
        $console = new DecisionsConsole('/project');
        $output = new BufferedOutput();
        self::assertSame(0, $console->doRun(new ArrayInput(['--help' => true]), $output));
        self::assertStringContainsString('Available commands:', $output->fetch());
        self::assertSame(LintCommand::INVALID, $console->doRun(new ArrayInput(['command' => 'chekc']), $output));
        self::assertSame("Decisions error: Command \"chekc\" is not defined. Did you mean check?\nRun \"decisions-lint list\" to see the available commands.\n", $output->fetch());
        self::assertSame(0, $console->doRun(new ArrayInput(['command' => 'li']), $output));
        self::assertStringContainsString('Available commands:', $output->fetch());
    }

    public function testUnknownCommandExplainsAmbiguityAndSuggestsNames(): void
    {
        $console = new DecisionsConsole('/project');
        self::assertNull($console->unknownCommand('check'));
        self::assertNull($console->unknownCommand('val'));
        self::assertSame('Command "zzzzzz" is not defined.', $console->unknownCommand('zzzzzz'));
        self::assertSame('Command "ruls" is not defined. Did you mean rules?', $console->unknownCommand('ruls'));
    }

    public function testRestoreShellVerbosityPutsTheEnvironmentBack(): void
    {
        $before = getenv('SHELL_VERBOSITY');
        try {
            putenv('SHELL_VERBOSITY=3');
            (new DecisionsConsole('/project'))->restoreShellVerbosity(false, null, null);
            self::assertFalse(getenv('SHELL_VERBOSITY'));
            self::assertArrayNotHasKey('SHELL_VERBOSITY', $_ENV);
            (new DecisionsConsole('/project'))->restoreShellVerbosity('1', '1', '1');
            self::assertSame('1', getenv('SHELL_VERBOSITY'));
            self::assertSame('1', $_ENV['SHELL_VERBOSITY']);
            self::assertSame('1', $_SERVER['SHELL_VERBOSITY']);
        } finally {
            unset($_ENV['SHELL_VERBOSITY'], $_SERVER['SHELL_VERBOSITY']);
            putenv($before === false ? 'SHELL_VERBOSITY' : 'SHELL_VERBOSITY=' . $before);
        }
    }
}
