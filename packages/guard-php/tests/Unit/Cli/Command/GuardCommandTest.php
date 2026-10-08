<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use Guard\Cli\ClosureOutput;
use Guard\Cli\Command\CheckCommand;
use Guard\Cli\Command\GuardCommand;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Guard\Cli\Command\GuardCommand
 * @uses \Guard\Cli\PolicyRun
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Cli\ClosureOutput
 * @uses \Guard\Cli\Command\CheckCommand
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Cli\CheckRun
 * @uses \Guard\Cli\BaselineFile
 * @uses \Guard\Cli\Command\BaselineCommand
 * @uses \Guard\Reporting\Baseline
 * @uses \Guard\Reporting\BaselineMatch
 * @uses \Guard\Reporting\Filtering\FindingFilter
 * @uses \Guard\Reporting\Filtering\ReportScope
 */
#[CoversClass(GuardCommand::class)]
#[UsesClass(\Guard\Cli\PolicyRun::class)]
#[UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Guard\Config\ImportResolver::class)]
#[UsesClass(ClosureOutput::class)]
#[UsesClass(CheckCommand::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Cli\CheckRun::class)]
#[UsesClass(\Guard\Cli\BaselineFile::class)]
#[UsesClass(\Guard\Cli\Command\BaselineCommand::class)]
#[UsesClass(\Guard\Reporting\Baseline::class)]
#[UsesClass(\Guard\Reporting\BaselineMatch::class)]
#[UsesClass(\Guard\Reporting\Filtering\FindingFilter::class)]
#[UsesClass(\Guard\Reporting\Filtering\ReportScope::class)]
final class GuardCommandTest extends TestCase
{
    public function testRunReportsAnUnknownOptionWithAPointerToTheHelpOfTheCommand(): void
    {
        $tester = new CommandTester(new CheckCommand('/project'));

        self::assertSame(2, $tester->execute(['--bogus' => true], ['capture_stderr_separately' => true]));
        self::assertSame('', $tester->getDisplay());
        self::assertSame("Guard error: The \"--bogus\" option does not exist.\nRun \"guard check --help\" to see its options.\n", $tester->getErrorOutput());
    }

    public function testRunReportsAnInvalidPolicyOnStandardError(): void
    {
        $tester = new CommandTester(new CheckCommand('/no/such/project'));

        self::assertSame(2, $tester->execute([], ['capture_stderr_separately' => true]));
        self::assertSame("Guard error: Guard configuration not found: /no/such/project/guard.yaml. Run guard init first.\n", $tester->getErrorOutput());
    }

    public function testCompleteSuggestsTheReportFormats(): void
    {
        self::assertSame(['text', 'human', 'ai', 'json'], (new CommandCompletionTester(new CheckCommand('/project')))->complete(['--format=']));
    }

    public function testConfigPathResolvesARelativePathAgainstTheProjectDirectory(): void
    {
        $command = new CheckCommand('/project');

        self::assertSame('/project/guard.yaml', $command->configPath(new ArrayInput([], $command->getDefinition())));
        self::assertSame('/project/conf/team.yaml', $command->configPath(new ArrayInput(['--config' => 'conf/team.yaml'], $command->getDefinition())));
        self::assertSame('/etc/guard.yaml', $command->configPath(new ArrayInput(['-c' => '/etc/guard.yaml'], $command->getDefinition())));
    }

    public function testConfigPathRejectsAnEmptyPath(): void
    {
        $command = new CheckCommand('/project');

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Provide a non-empty --config path, such as --config=guard.yaml.');

        $command->configPath(new ArrayInput(['--config' => ''], $command->getDefinition()));
    }

    public function testFormatReadsTextOrJson(): void
    {
        $command = new CheckCommand('/project');

        self::assertSame('text', $command->format(new ArrayInput(['--format' => 'text'], $command->getDefinition())));
        self::assertSame('json', $command->format(new ArrayInput(['--format' => 'json'], $command->getDefinition())));
    }

    public function testFormatRejectsAnUnsupportedFormat(): void
    {
        $command = new CheckCommand('/project');

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Unsupported --format "xml". Use --format=text, human, ai or json.');

        $command->format(new ArrayInput(['--format' => 'xml'], $command->getDefinition()));
    }

    public function testErrorWritesToStandardErrorEvenWhenQuiet(): void
    {
        $errors = new BufferedOutput(OutputInterface::VERBOSITY_QUIET);
        $output = new ClosureOutput(static function (): void {
        }, $errors);

        (new CheckCommand('/project'))->error($output, 'Something <is> wrong.', 'Fix it.');

        self::assertSame("Guard error: Something <is> wrong.\nFix it.\n", $errors->fetch());
    }
}
