<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use Closure;
use Guard\Cli\Command\FixCommand;
use Guard\Cli\Command\GuardCommand;
use Guard\Cli\FormatDetector;
use Guard\Cli\PolicyRun;
use Guard\Collect\Collector;
use Guard\Collect\Filesystem\Discovery;
use Guard\Collect\Filesystem\Snapshot;
use Guard\Collect\Filesystem\WalkQueue;
use Guard\Config\ComponentLoader;
use Guard\Config\Configuration;
use Guard\Config\ConfigurationLoader;
use Guard\Config\DocumentMerger;
use Guard\Config\ImportResolver;
use Guard\Config\Reader\ExtensionConfigReader;
use Guard\Config\RuleReader;
use Guard\Config\Schema;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Execution\Pipeline;
use Guard\Execution\Registry;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Policy\Context;
use Guard\Policy\Diagnostic\RuleMessages;
use Guard\Policy\FieldConstraints;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\Policy;
use Guard\Policy\PolicyBinding;
use Guard\Repair\AtomicWriter;
use Guard\Repair\ChangeSet;
use Guard\Reporting\ChangeDiff;
use Guard\Reporting\Reporter;
use Guard\Structure\Document\DataDocument;
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
#[UsesClass(Discovery::class)]
#[UsesClass(Snapshot::class)]
#[UsesClass(WalkQueue::class)]
#[UsesClass(ExtensionConfigReader::class)]
#[UsesClass(RuleReader::class)]
#[UsesClass(ComponentLoader::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(FieldConstraints::class)]
#[UsesClass(GuardCommand::class)]
#[UsesClass(PolicyRun::class)]
#[UsesClass(Collector::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
#[UsesClass(DocumentMerger::class)]
#[UsesClass(ImportResolver::class)]
#[UsesClass(Schema::class)]
#[UsesClass(DataDocument::class)]
#[UsesClass(AtomicWriter::class)]
#[UsesClass(ChangeSet::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Registry::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
#[UsesClass(Reporter::class)]
#[UsesClass(ChangeDiff::class)]
#[UsesClass(RuleMessages::class)]
#[UsesClass(FormatDetector::class)]
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
        $registry->addPolicy('rewrite', new class ([], static fn (): Plan => new Plan([], [new FileChange($root . '/a.txt', 'old', 'new')])) implements Policy {
            /** @param array<string, Input> $inputs
             * @param Closure(InputSet, Context): Plan $callback
             */
            public function __construct(private array $inputs, private Closure $callback)
            {
            }
            public function inputs(Context $context): array
            {
                return $this->inputs;
            }
            public function evaluate(InputSet $inputs, Context $context): Plan
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
        $registry->addPolicy('rewrite', new class ([], static fn (): Plan => new Plan([], [new FileChange($root . '/a.txt', 'old', 'new')])) implements Policy {
            /** @param array<string, Input> $inputs
             * @param Closure(InputSet, Context): Plan $callback
             */
            public function __construct(private array $inputs, private Closure $callback)
            {
            }
            public function inputs(Context $context): array
            {
                return $this->inputs;
            }
            public function evaluate(InputSet $inputs, Context $context): Plan
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
