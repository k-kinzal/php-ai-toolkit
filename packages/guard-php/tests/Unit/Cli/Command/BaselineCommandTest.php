<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use Closure;
use FilesystemIterator;
use Guard\Cli\PolicyRun;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Execution\Registry;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Guard\Cli\Command\BaselineCommand
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
 * @uses \Guard\Reporting\Baseline
 * @uses \Guard\Reporting\BaselineMatch
 * @uses \Guard\Reporting\Filtering\FindingFilter
 * @uses \Guard\Reporting\Filtering\ReportScope
 */
#[CoversClass(\Guard\Cli\Command\BaselineCommand::class)]
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
#[UsesClass(\Guard\Reporting\Baseline::class)]
#[UsesClass(\Guard\Reporting\BaselineMatch::class)]
#[UsesClass(\Guard\Reporting\Filtering\FindingFilter::class)]
#[UsesClass(\Guard\Reporting\Filtering\ReportScope::class)]
final class BaselineCommandTest extends TestCase
{
    public function testExecuteCapturesAllDiagnosticsAndNeverRepairsProjectFiles(): void
    {
        $project = new class (['guard.yaml' => "version: 1\n", 'a.txt' => 'old']) {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }


            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        $finding = new Finding('a.txt', 'problem', 'required', 'Old content. Replace it with new.', true);
        $registry = new Registry();
        $registry->addPolicy('test', new class ([], static fn (): Plan => new Plan([$finding], [new FileChange($project->root . '/a.txt', 'old', 'new')])) implements \Guard\Policy\Policy {
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
        $tester = new CommandTester(new \Guard\Cli\Command\BaselineCommand($project->root, $registry));
        try {
            self::assertSame(0, $tester->execute(['--format' => 'json', '--output' => 'custom.json']));
            self::assertStringContainsString('"count": 1', $tester->getDisplay());
            self::assertSame('old', file_get_contents($project->root . '/a.txt'));
            $baseline = (string) file_get_contents($project->root . '/custom.json');
            self::assertStringContainsString($finding->message, $baseline);
            self::assertSame(0, $tester->execute(['--output' => 'custom.json', '--format' => 'text']));
            self::assertSame($baseline, file_get_contents($project->root . '/custom.json'));
            self::assertStringContainsString('Recorded 1 diagnostics', $tester->getDisplay());
        } finally {
            $project->remove();
        }
    }

    public function testExecutePreservesTheOldBaselineWhenEvaluationFails(): void
    {
        $project = new class (['guard.yaml' => "version: 1\n", 'guard-baseline.json' => '{"version":1,"entries":[]}']) {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }


            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        $registry = new Registry();
        $registry->addPolicy('test', new class ([], static function (): Plan {
            throw new PolicyException('Invalid input. Correct it.');
        }) implements \Guard\Policy\Policy
        {
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
        try {
            $tester = new CommandTester(new \Guard\Cli\Command\BaselineCommand($project->root, $registry));
            self::assertSame(2, $tester->execute([]));
            self::assertSame('{"version":1,"entries":[]}', file_get_contents($project->root . '/guard-baseline.json'));
        } finally {
            $project->remove();
        }
    }
}
