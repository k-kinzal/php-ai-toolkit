<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use Closure;
use FilesystemIterator;
use Guard\Cli\PolicyRun;
use Guard\Execution\FileChange;
use Guard\Execution\Plan;
use Guard\Extension\Registry;
use Guard\Policy\PolicyException;
use Guard\Reporting\Finding;
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
 * @uses \Guard\Extension\ExtensionLoader
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\FieldConstraints
 * @uses \Guard\Cli\FormatDetector
 * @uses \Guard\Cli\Command\GuardCommand
 * @uses \Guard\Cli\PolicyRun
 * @uses \Guard\Collect\Collector
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Config\Schema
 * @uses \Guard\Document\DataDocument
 * @uses \Guard\Execution\AtomicWriter
 * @uses \Guard\Execution\ChangeSet
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Pipeline
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\Registry
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 * @uses \Guard\Reporting\ChangeDiff
 * @uses \Guard\Reporting\RuleMessages
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
#[UsesClass(\Guard\Extension\ExtensionLoader::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\FieldConstraints::class)]
#[UsesClass(\Guard\Cli\Command\GuardCommand::class)]
#[UsesClass(PolicyRun::class)]
#[UsesClass(\Guard\Collect\Collector::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Guard\Config\ImportResolver::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Document\DataDocument::class)]
#[UsesClass(\Guard\Execution\AtomicWriter::class)]
#[UsesClass(\Guard\Execution\ChangeSet::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(\Guard\Execution\Pipeline::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Registry::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
#[UsesClass(\Guard\Reporting\Reporter::class)]
#[UsesClass(\Guard\Reporting\ChangeDiff::class)]
#[UsesClass(\Guard\Reporting\RuleMessages::class)]
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
            /** @param array<string, \Guard\Collect\Input> $inputs
             * @param Closure(\Guard\Collect\InputSet, \Guard\Execution\Context): Plan $callback
             */
            public function __construct(private array $inputs, private Closure $callback)
            {
            }
            public function inputs(\Guard\Execution\Context $context): array
            {
                return $this->inputs;
            }
            public function evaluate(\Guard\Collect\InputSet $inputs, \Guard\Execution\Context $context): Plan
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
            /** @param array<string, \Guard\Collect\Input> $inputs
             * @param Closure(\Guard\Collect\InputSet, \Guard\Execution\Context): Plan $callback
             */
            public function __construct(private array $inputs, private Closure $callback)
            {
            }
            public function inputs(\Guard\Execution\Context $context): array
            {
                return $this->inputs;
            }
            public function evaluate(\Guard\Collect\InputSet $inputs, \Guard\Execution\Context $context): Plan
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
