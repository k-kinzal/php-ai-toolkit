<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use Closure;
use Guard\Cli\BaselineFile;
use Guard\Cli\CheckRun;
use Guard\Cli\Command\BaselineCommand;
use Guard\Cli\Command\CheckCommand;
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
use Guard\Reporting\Baseline;
use Guard\Reporting\BaselineMatch;
use Guard\Reporting\ChangeDiff;
use Guard\Reporting\Filtering\FindingFilter;
use Guard\Reporting\Filtering\ReportScope;
use Guard\Reporting\Reporter;
use Guard\Structure\Document\DataDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Guard\Cli\Command\CheckCommand
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
 * @uses \Guard\Cli\CheckRun
 * @uses \Guard\Cli\BaselineFile
 * @uses \Guard\Cli\Command\BaselineCommand
 * @uses \Guard\Reporting\Baseline
 * @uses \Guard\Reporting\BaselineMatch
 * @uses \Guard\Reporting\Filtering\FindingFilter
 * @uses \Guard\Reporting\Filtering\ReportScope
 */
#[CoversClass(CheckCommand::class)]
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
#[UsesClass(CheckRun::class)]
#[UsesClass(BaselineFile::class)]
#[UsesClass(BaselineCommand::class)]
#[UsesClass(Baseline::class)]
#[UsesClass(BaselineMatch::class)]
#[UsesClass(FindingFilter::class)]
#[UsesClass(ReportScope::class)]
final class CheckCommandTest extends TestCase
{
    public function testConfigureDeclaresTheOptionsAndTheExitCodes(): void
    {
        $command = new CheckCommand('/project');

        self::assertSame('check', $command->getName());
        self::assertSame('c', $command->getDefinition()->getOption('config')->getShortcut());
        self::assertNull($command->getDefinition()->getOption('format')->getDefault());
        self::assertSame('', $command->getHelp());
        self::assertFalse($command->getDefinition()->hasOption('dry-run'));
    }

    public function testExecuteReportsWithoutWritingTheRepairs(): void
    {
        $root = sys_get_temp_dir() . '/guard-check-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        file_put_contents($root . '/a.txt', 'old');
        $finding = new Finding('a.txt', 'example.content', 'required', 'Replace old with new.');
        $registry = new Registry();
        $registry->addPolicy('rewrite', new class ([], static fn (): Plan => new Plan([$finding], [new FileChange($root . '/a.txt', 'old', 'new')])) implements Policy {
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
        $tester = new CommandTester(new CheckCommand($root, $registry));

        self::assertSame(1, $tester->execute(['--format' => 'json']));
        self::assertSame('old', file_get_contents($root . '/a.txt'));
        self::assertStringContainsString('"rule": "example.content"', $tester->getDisplay());
    }
}
