<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use Guard\Cli\ClosureOutput;
use Guard\Cli\Command\CheckCommand;
use Guard\Cli\Command\FixCommand;
use Guard\Cli\Command\GuardCommand;
use Guard\Cli\Command\InitCommand;
use Guard\Cli\GuardConsole;
use Guard\Init\PresetCatalog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Guard\Cli\GuardConsole
 * @uses \Guard\Cli\ClosureOutput
 * @uses \Guard\Cli\Command\FixCommand
 * @uses \Guard\Cli\Command\CheckCommand
 * @uses \Guard\Cli\FormatDetector
 * @uses \Guard\Cli\Command\GuardCommand
 * @uses \Guard\Cli\Command\InitCommand
 * @uses \Guard\Init\PresetCatalog
 * @uses \Guard\Cli\Command\RulesCommand
 * @uses \Guard\Cli\CheckRun
 * @uses \Guard\Cli\BaselineFile
 * @uses \Guard\Cli\Command\BaselineCommand
 * @uses \Guard\Reporting\Baseline
 * @uses \Guard\Reporting\BaselineMatch
 * @uses \Guard\Reporting\Filtering\FindingFilter
 * @uses \Guard\Reporting\Filtering\ReportScope
 */
#[CoversClass(GuardConsole::class)]
#[UsesClass(ClosureOutput::class)]
#[UsesClass(FixCommand::class)]
#[UsesClass(CheckCommand::class)]
#[UsesClass(GuardCommand::class)]
#[UsesClass(InitCommand::class)]
#[UsesClass(PresetCatalog::class)]
#[UsesClass(\Guard\Cli\FormatDetector::class)]
#[UsesClass(\Guard\Cli\Command\RulesCommand::class)]
#[UsesClass(\Guard\Cli\CheckRun::class)]
#[UsesClass(\Guard\Cli\BaselineFile::class)]
#[UsesClass(\Guard\Cli\Command\BaselineCommand::class)]
#[UsesClass(\Guard\Reporting\Baseline::class)]
#[UsesClass(\Guard\Reporting\BaselineMatch::class)]
#[UsesClass(\Guard\Reporting\Filtering\FindingFilter::class)]
#[UsesClass(\Guard\Reporting\Filtering\ReportScope::class)]
final class GuardConsoleTest extends TestCase
{
    public function testGetHelpSaysWhatGuardDoes(): void
    {
        $help = (new GuardConsole(sys_get_temp_dir()))->getHelp();

        self::assertStringStartsWith('guard <info>1.0.0</info>', $help);
        self::assertStringContainsString('Running guard without a command runs check.', $help);
    }

    public function testGetDefinitionSaysHelpListsTheCommandsWithoutACommand(): void
    {
        $definition = (new GuardConsole(sys_get_temp_dir()))->getDefinition();

        self::assertSame('Display help for the given command, or list the commands when none is given', $definition->getOption('help')->getDescription());
        self::assertSame('help', $definition->getOptionForShortcut('h')->getName());
        self::assertTrue($definition->hasOption('version'));
    }

    public function testDoRunListsTheCommandsForHelpWithoutACommand(): void
    {
        $output = new BufferedOutput();

        self::assertSame(0, (new GuardConsole(sys_get_temp_dir()))->doRun(new ArgvInput(['guard', '-h']), $output));
        self::assertStringContainsString('Available commands:', $output->fetch());
    }

    public function testDoRunShowsTheHelpOfTheNamedCommand(): void
    {
        $output = new BufferedOutput();

        self::assertSame(0, (new GuardConsole(sys_get_temp_dir()))->doRun(new ArgvInput(['guard', 'check', '--help']), $output));
        self::assertStringNotContainsString('Help:', $output->fetch());
    }

    public function testDoRunRunsACommandItsNameAbbreviates(): void
    {
        $output = new BufferedOutput();

        self::assertSame(0, (new GuardConsole(sys_get_temp_dir()))->doRun(new ArgvInput(['guard', 'ini', '--help']), $output));
        self::assertStringContainsString('Create a policy file', $output->fetch());
    }

    public function testDoRunReportsAnUnknownCommandWithTheCommandItResembles(): void
    {
        $errors = new BufferedOutput();
        $output = new ClosureOutput(static function (): void {
        }, $errors);

        self::assertSame(2, (new GuardConsole(sys_get_temp_dir()))->doRun(new ArgvInput(['guard', 'chek']), $output));
        self::assertSame("Guard error: Command \"chek\" is not defined. Did you mean check?\nRun \"guard list\" to see the available commands.\n", $errors->fetch());
    }

    public function testDoRunHonoursQuietForInProcessRuns(): void
    {
        $shellVerbosity = getenv('SHELL_VERBOSITY');
        $output = new BufferedOutput();

        self::assertSame(0, (new GuardConsole(sys_get_temp_dir()))->doRun(new ArgvInput(['guard', 'list', '--quiet']), $output));
        self::assertSame(OutputInterface::VERBOSITY_QUIET, $output->getVerbosity());
        self::assertSame('', $output->fetch());
        self::assertSame($shellVerbosity, getenv('SHELL_VERBOSITY'));
    }

    public function testRestoreShellVerbositySetsAndClearsEveryPlaceItIsKept(): void
    {
        $console = new GuardConsole(sys_get_temp_dir());
        $before = [getenv('SHELL_VERBOSITY'), $_ENV['SHELL_VERBOSITY'] ?? null, $_SERVER['SHELL_VERBOSITY'] ?? null];

        $console->restoreShellVerbosity('1', '2', '3');

        self::assertSame('1', getenv('SHELL_VERBOSITY'));
        self::assertSame('2', $_ENV['SHELL_VERBOSITY']);
        self::assertSame('3', $_SERVER['SHELL_VERBOSITY']);

        $console->restoreShellVerbosity(false, null, null);

        self::assertFalse(getenv('SHELL_VERBOSITY'));
        self::assertArrayNotHasKey('SHELL_VERBOSITY', $_ENV);
        self::assertArrayNotHasKey('SHELL_VERBOSITY', $_SERVER);

        $console->restoreShellVerbosity(...$before);
    }

    public function testUnknownCommandAcceptsANameOrAnUnambiguousStartOfOne(): void
    {
        $console = new GuardConsole(sys_get_temp_dir());

        self::assertNull($console->unknownCommand('check'));
        self::assertNull($console->unknownCommand('fi'));
        self::assertNull($console->unknownCommand('_complete'));
    }

    public function testUnknownCommandNamesTheCommandsAnAmbiguousNameCouldMean(): void
    {
        self::assertSame('Command "c" is ambiguous. Name one of: check, completion.', (new GuardConsole(sys_get_temp_dir()))->unknownCommand('c'));
    }

    public function testUnknownCommandSuggestsOnlySimilarCommands(): void
    {
        $console = new GuardConsole(sys_get_temp_dir());

        self::assertSame('Command "inti" is not defined. Did you mean init?', $console->unknownCommand('inti'));
        self::assertSame('Command "bad" is not defined.', $console->unknownCommand('bad'));
    }
}
