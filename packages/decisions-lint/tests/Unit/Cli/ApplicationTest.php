<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use DecisionsLint\Cli\Application;
use DecisionsLint\Cli\ClosureOutput;
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
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @covers \DecisionsLint\Cli\Application
 * @uses \DecisionsLint\Cli\ClosureOutput
 * @uses \DecisionsLint\Cli\Command\CheckCommand
 * @uses \DecisionsLint\Cli\Command\InitCommand
 * @uses \DecisionsLint\Cli\Command\LintCommand
 * @uses \DecisionsLint\Cli\Command\PlanCommand
 * @uses \DecisionsLint\Cli\Command\RulesCommand
 * @uses \DecisionsLint\Cli\Command\ValidateCommand
 * @uses \DecisionsLint\Cli\DecisionsConsole
 */
#[CoversClass(Application::class)]
#[UsesClass(ClosureOutput::class)]
#[UsesClass(CheckCommand::class)]
#[UsesClass(InitCommand::class)]
#[UsesClass(LintCommand::class)]
#[UsesClass(PlanCommand::class)]
#[UsesClass(RulesCommand::class)]
#[UsesClass(ValidateCommand::class)]
#[UsesClass(DecisionsConsole::class)]
final class ApplicationTest extends TestCase
{
    public function testRunReportsTheVersionAndHelp(): void
    {
        $output = '';
        $app = new Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['--version']));
        self::assertSame("decisions-lint 1.0.0\n", $output);
        $output = '';
        self::assertSame(0, $app->run(['--help']));
        self::assertStringContainsString('Running decisions-lint without a command runs check.', $output);
        self::assertMatchesRegularExpression('/^  check +Judge /m', $output);
        self::assertMatchesRegularExpression('/^  plan +List /m', $output);
    }

    public function testRunRejectsAnUnknownOptionWithAPointerToTheCommandHelp(): void
    {
        $output = '';
        $app = new Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(2, $app->run(['plan', '--bogus']));
        self::assertSame("Decisions error: The \"--bogus\" option does not exist.\nRun \"decisions-lint plan --help\" to see its options.\n", $output);
    }

    public function testConsoleHasTheCommands(): void
    {
        $console = (new Application(sys_get_temp_dir()))->console();
        self::assertSame('decisions-lint', $console->getName());
        self::assertTrue($console->has('check'));
        self::assertTrue($console->has('init'));
    }

    public function testOutputWritesToTheProcessStreamsWithoutAnInjectedSink(): void
    {
        self::assertInstanceOf(ConsoleOutput::class, (new Application(sys_get_temp_dir()))->output());
    }

    public function testOutputWritesToTheInjectedSink(): void
    {
        $output = '';
        (new Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        }))->output()->writeln('<info>raw</info>');
        self::assertSame("raw\n", $output);
    }
}
