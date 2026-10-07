<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Toolkit\DocGen\Cli\ClosureOutput;
use Toolkit\DocGen\Cli\DocGenCliArgumentParser;
use Toolkit\DocGen\Cli\DocGenCommand;
use Toolkit\DocGen\Cli\DocGenConsole;
use Toolkit\DocGen\Cli\DocGenHelpText;

/**
 * @covers \Toolkit\DocGen\Cli\DocGenConsole
 * @uses \Toolkit\DocGen\Cli\ClosureOutput
 * @uses \Toolkit\DocGen\Cli\DocGenCliArgumentParser
 * @uses \Toolkit\DocGen\Cli\DocGenCommand
 * @uses \Toolkit\DocGen\Cli\DocGenHelpText
 */
#[CoversClass(DocGenConsole::class)]
#[UsesClass(ClosureOutput::class)]
#[UsesClass(DocGenCliArgumentParser::class)]
#[UsesClass(DocGenCommand::class)]
#[UsesClass(DocGenHelpText::class)]
final class DocGenConsoleTest extends TestCase
{
    public function testGetNameIsTheNameOfItsOnlyCommand(): void
    {
        $console = new DocGenConsole(sys_get_temp_dir());

        self::assertSame('docgen', $console->getName());
        self::assertSame(DocGenConsole::VERSION, $console->getVersion());
        self::assertTrue($console->has('docgen'));
    }

    public function testDoRunPrintsTheVersion(): void
    {
        $output = new BufferedOutput();

        self::assertSame(0, (new DocGenConsole(sys_get_temp_dir()))->doRun(new ArgvInput(['docgen', '--version']), $output));
        self::assertSame("docgen 1.0.0\n", $output->fetch());
    }

    public function testDoRunHonoursQuietForInProcessRuns(): void
    {
        $shellVerbosity = getenv('SHELL_VERBOSITY');
        $output = new BufferedOutput();

        self::assertSame(0, (new DocGenConsole(sys_get_temp_dir()))->doRun(new ArgvInput(['docgen', '--quiet', '--help']), $output));
        self::assertSame(OutputInterface::VERBOSITY_QUIET, $output->getVerbosity());
        self::assertSame('', $output->fetch());
        self::assertSame($shellVerbosity, getenv('SHELL_VERBOSITY'));
    }

    public function testRestoreShellVerbositySetsAndClearsEveryPlaceItIsKept(): void
    {
        $console = new DocGenConsole(sys_get_temp_dir());
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
}
