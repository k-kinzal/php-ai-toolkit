<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use Closure;
use Guard\Cli\Command\CheckCommand;
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
#[UsesClass(\Guard\Cli\CheckRun::class)]
#[UsesClass(\Guard\Cli\BaselineFile::class)]
#[UsesClass(\Guard\Cli\Command\BaselineCommand::class)]
#[UsesClass(\Guard\Reporting\Baseline::class)]
#[UsesClass(\Guard\Reporting\BaselineMatch::class)]
#[UsesClass(\Guard\Reporting\Filtering\FindingFilter::class)]
#[UsesClass(\Guard\Reporting\Filtering\ReportScope::class)]
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
        $registry->addPolicy('rewrite', new class ([], static fn (): Plan => new Plan([$finding], [new FileChange($root . '/a.txt', 'old', 'new')])) implements \Guard\Policy\Policy {
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
        $tester = new CommandTester(new CheckCommand($root, $registry));

        self::assertSame(1, $tester->execute(['--format' => 'json']));
        self::assertSame('old', file_get_contents($root . '/a.txt'));
        self::assertStringContainsString('"rule": "example.content"', $tester->getDisplay());
    }
}
