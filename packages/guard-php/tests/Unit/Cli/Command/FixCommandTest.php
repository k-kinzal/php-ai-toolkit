<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use Closure;
use Guard\Cli\Command\FixCommand;
use Guard\Cli\PolicyRun;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Execution\Registry;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Guard\Cli\Command\FixCommand
 * @uses \Guard\Collect\Filesystem\Discovery
 * @uses \Guard\Collect\Filesystem\Snapshot
 * @uses \Guard\Collect\Filesystem\WalkQueue
 * @uses \Guard\Config\Reader\ExtensionConfigReader
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Config\ComponentLoader
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Policy\FieldConstraints
 * @uses \Guard\Cli\FormatDetector
 * @uses \Guard\Cli\Command\GuardCommand
 * @uses \Guard\Cli\PolicyRun
 * @uses \Guard\Collect\Collector
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Config\Schema
 * @uses \Guard\Structure\Document\DataDocument
 * @uses \Guard\Repair\AtomicWriter
 * @uses \Guard\Repair\ChangeSet
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Execution\Pipeline
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Execution\Registry
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Reporting\ChangeDiff
 * @uses \Guard\Policy\Diagnostic\RuleMessages
 * @uses \Guard\Reporting\Reporter
 */
#[CoversClass(FixCommand::class)]
#[UsesClass(\Guard\Collect\Filesystem\Discovery::class)]
#[UsesClass(\Guard\Collect\Filesystem\Snapshot::class)]
#[UsesClass(\Guard\Collect\Filesystem\WalkQueue::class)]
#[UsesClass(\Guard\Config\Reader\ExtensionConfigReader::class)]
#[UsesClass(\Guard\Config\RuleReader::class)]
#[UsesClass(\Guard\Config\ComponentLoader::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\FieldConstraints::class)]
#[UsesClass(\Guard\Cli\Command\GuardCommand::class)]
#[UsesClass(PolicyRun::class)]
#[UsesClass(\Guard\Collect\Collector::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Guard\Config\ImportResolver::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Structure\Document\DataDocument::class)]
#[UsesClass(\Guard\Repair\AtomicWriter::class)]
#[UsesClass(\Guard\Repair\ChangeSet::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(\Guard\Execution\Pipeline::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Registry::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
#[UsesClass(\Guard\Reporting\Reporter::class)]
#[UsesClass(\Guard\Reporting\ChangeDiff::class)]
#[UsesClass(\Guard\Policy\Diagnostic\RuleMessages::class)]
#[UsesClass(\Guard\Cli\FormatDetector::class)]
final class FixCommandTest extends TestCase
{
    public function testConfigureDeclaresTheDryRunAndTheExitCodes(): void
    {
        $command = new FixCommand('/project');

        self::assertSame('fix', $command->getName());
        self::assertSame('', $command->getHelp());
        self::assertFalse($command->getDefinition()->getOption('dry-run')->acceptValue());
        self::assertTrue($command->getDefinition()->hasOption('format'));
    }

    public function testExecuteWritesTheRepairs(): void
    {
        $root = sys_get_temp_dir() . '/guard-apply-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        file_put_contents($root . '/a.txt', 'old');
        $registry = new Registry();
        $registry->addPolicy('rewrite', new class ([], static fn (): Plan => new Plan([], [new FileChange($root . '/a.txt', 'old', 'new')])) implements \Guard\Policy\Policy {
            /** @param array<string, \Guard\Input\Input> $inputs
             * @param Closure(\Guard\Input\InputSet, \Guard\Policy\Context): Plan $callback
             */
            public function __construct(private array $inputs, private Closure $callback)
            {
            }
            public function inputs(\Guard\Policy\Context $context): array
            {
                return $this->inputs;
            }
            public function evaluate(\Guard\Input\InputSet $inputs, \Guard\Policy\Context $context): Plan
            {
                return ($this->callback)($inputs, $context);
            }
        });
        $tester = new CommandTester(new FixCommand($root, $registry));

        self::assertSame(0, $tester->execute([]));
        self::assertSame('new', file_get_contents($root . '/a.txt'));
        self::assertStringContainsString('changed: a.txt', $tester->getDisplay());
    }

    public function testExecuteListsTheRepairsOfADryRunWithoutWritingThem(): void
    {
        $root = sys_get_temp_dir() . '/guard-apply-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        file_put_contents($root . '/a.txt', 'old');
        $registry = new Registry();
        $registry->addPolicy('rewrite', new class ([], static fn (): Plan => new Plan([], [new FileChange($root . '/a.txt', 'old', 'new')])) implements \Guard\Policy\Policy {
            /** @param array<string, \Guard\Input\Input> $inputs
             * @param Closure(\Guard\Input\InputSet, \Guard\Policy\Context): Plan $callback
             */
            public function __construct(private array $inputs, private Closure $callback)
            {
            }
            public function inputs(\Guard\Policy\Context $context): array
            {
                return $this->inputs;
            }
            public function evaluate(\Guard\Input\InputSet $inputs, \Guard\Policy\Context $context): Plan
            {
                return ($this->callback)($inputs, $context);
            }
        });
        $tester = new CommandTester(new FixCommand($root, $registry));

        self::assertSame(0, $tester->execute(['--dry-run' => true, '--format' => 'text']));
        self::assertSame('old', file_get_contents($root . '/a.txt'));
        self::assertStringContainsString('would change: a.txt', $tester->getDisplay());
        self::assertStringContainsString('-old', $tester->getDisplay());
        self::assertStringContainsString('+new', $tester->getDisplay());
        self::assertStringContainsString('Dry run: no files were written.', $tester->getDisplay());
    }
}
