<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use DecisionsLint\Cli\Command\CheckCommand;
use DecisionsLint\Cli\Command\LintCommand;
use DecisionsLint\Cli\FormatDetector;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\Fake\FakeTransport;
use DecisionsLint\Reporting\ReporterFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \DecisionsLint\Cli\Command\LintCommand
 * @uses \DecisionsLint\Cli\Command\CheckCommand
 * @uses \DecisionsLint\Cli\FormatDetector
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Provider\Fake\FakeTransport
 */
#[CoversClass(LintCommand::class)]
#[UsesClass(CheckCommand::class)]
#[UsesClass(FormatDetector::class)]
#[UsesClass(LintException::class)]
#[UsesClass(FakeTransport::class)]
final class LintCommandTest extends TestCase
{
    public function testRunReportsACommandLineItCannotRead(): void
    {
        $tester = new CommandTester(new CheckCommand('/project'));
        self::assertSame(LintCommand::INVALID, $tester->execute(['--nope' => true]));
        self::assertStringContainsString('Decisions error: The "--nope" option does not exist.', $tester->getDisplay());
        self::assertStringContainsString('Run "decisions-lint check --help" to see its options.', $tester->getDisplay());
    }

    public function testCompleteSuggestsTheFormats(): void
    {
        $command = new CheckCommand('/project');
        $suggestions = new CompletionSuggestions();
        $input = CompletionInput::fromString('decisions-lint check --format ', 3);
        $input->bind($command->getDefinition());
        $command->complete($input, $suggestions);
        self::assertSame(ReporterFactory::FORMATS, array_map('strval', $suggestions->getValueSuggestions()));
    }

    public function testDirectoryReturnsTheProjectDirectory(): void
    {
        self::assertSame('/project', (new CheckCommand('/project'))->directory());
    }

    public function testTransportReturnsTheInjectedTransportOrNull(): void
    {
        self::assertNull((new CheckCommand('/project'))->transport());
        $transport = new FakeTransport();
        self::assertSame($transport, (new CheckCommand('/project', $transport))->transport());
    }

    public function testConfigPathResolvesRelativeToTheDirectory(): void
    {
        $command = new CheckCommand('/project');
        $command->mergeApplicationDefinition();
        $input = new ArrayInput(['--config' => 'team.yaml'], $command->getDefinition());
        self::assertSame('/project/team.yaml', $command->configPath($input));
        self::assertSame('/abs/decisions.yaml', $command->configPath(new ArrayInput(['--config' => '/abs/decisions.yaml'], $command->getDefinition())));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Provide a non-empty --config path');
        $command->configPath(new ArrayInput(['--config' => ''], $command->getDefinition()));
    }

    public function testFormatValidatesAgainstTheAllowedFormats(): void
    {
        $command = new CheckCommand('/project');
        self::assertSame('json', $command->format(new ArrayInput(['--format' => 'json'], $command->getDefinition()), ['text', 'json']));
        self::assertContains($command->format(new ArrayInput([], $command->getDefinition()), ['text', 'ai']), ['text', 'ai']);
        self::assertSame('text', $command->format(new ArrayInput([], $command->getDefinition()), ['text']));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Unsupported --format "yaml". Use --format=text, json.');
        $command->format(new ArrayInput(['--format' => 'yaml'], $command->getDefinition()), ['text', 'json']);
    }

    public function testRuleIdsReadsTheRepeatableOption(): void
    {
        $command = new CheckCommand('/project');
        self::assertSame(['a.b', 'c'], $command->ruleIds(new ArrayInput(['--rule' => ['a.b', ' c ']], $command->getDefinition())));
        self::assertSame([], $command->ruleIds(new ArrayInput([], $command->getDefinition())));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Provide a rule id for --rule');
        $command->ruleIds(new ArrayInput(['--rule' => ['']], $command->getDefinition()));
    }

    public function testPathsReadsTheArguments(): void
    {
        $command = new CheckCommand('/project');
        self::assertSame(['src/A.php', 'tests'], $command->paths(new ArrayInput(['paths' => ['src/A.php', ' tests ', '']], $command->getDefinition())));
        self::assertSame([], $command->paths(new ArrayInput([], $command->getDefinition())));
    }

    public function testErrorWritesToStandardErrorEvenWhenQuiet(): void
    {
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_QUIET);
        (new CheckCommand('/project'))->error($output, 'Broken.', 'Try again.');
        self::assertSame("Decisions error: Broken.\nTry again.\n", $output->fetch());
    }
}
