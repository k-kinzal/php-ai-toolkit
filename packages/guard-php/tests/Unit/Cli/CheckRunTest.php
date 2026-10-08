<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use Closure;
use FilesystemIterator;
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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Guard\Cli\CheckRun
 * @uses \Guard\Cli\Command\CheckCommand
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
 * @uses \Guard\Cli\BaselineFile
 * @uses \Guard\Cli\Command\BaselineCommand
 * @uses \Guard\Reporting\Baseline
 * @uses \Guard\Reporting\BaselineMatch
 * @uses \Guard\Reporting\Filtering\FindingFilter
 * @uses \Guard\Reporting\Filtering\ReportScope
 */
#[CoversClass(\Guard\Cli\CheckRun::class)]
#[UsesClass(CheckCommand::class)]
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
#[UsesClass(\Guard\Cli\BaselineFile::class)]
#[UsesClass(\Guard\Cli\Command\BaselineCommand::class)]
#[UsesClass(\Guard\Reporting\Baseline::class)]
#[UsesClass(\Guard\Reporting\BaselineMatch::class)]
#[UsesClass(\Guard\Reporting\Filtering\FindingFilter::class)]
#[UsesClass(\Guard\Reporting\Filtering\ReportScope::class)]
final class CheckRunTest extends TestCase
{
    /**
     * @dataProvider providerFormats
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerFormats')]
    public function testRunFiltersOnlyTheDisplayAndKeepsHiddenErrorsInTheExitStatus(string $format, string $status): void
    {
        $project = new class (['guard.yaml' => "version: 1\n"]) {
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
        $error = new Finding('a', 'problem', 'required', 'Wrong value. Set it to 1.');
        $warning = new Finding('b', 'suggestion', 'recommended', 'Wrong value. Set it to 2.', true);
        $registry = new Registry();
        $registry->addPolicy('test', new class ([], static fn (): Plan => new Plan([$error, $warning], [])) implements \Guard\Policy\Policy {
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
        $tester = new CommandTester(new CheckCommand($project->root, $registry));
        try {
            self::assertSame(1, $tester->execute(['--level' => 'warning', '--fixable' => true, '--format' => $format]));
            self::assertStringContainsString('suggestion', $tester->getDisplay());
            self::assertStringNotContainsString('Wrong value. Set it to 1.', $tester->getDisplay());
            self::assertStringContainsString($status, $tester->getDisplay());
            self::assertSame(1, $tester->execute(['--query' => 'no-match', '--format' => 'text']));
            self::assertStringContainsString('Showing 0 of 2', $tester->getDisplay());
            self::assertSame(2, $tester->execute(['--query' => ' ']));
            self::assertSame(2, $tester->execute(['--level' => 'fatal']));
        } finally {
            $project->remove();
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function providerFormats(): iterable
    {
        yield 'human' => ['text', 'Guard found required violations.'];
        yield 'ai' => ['ai', 'Guard found required violations.'];
        yield 'json' => ['json', '"success": false'];
    }

    public function testBaselinePathDistinguishesDefaultExplicitAndDisabledBaselines(): void
    {
        $project = new class (['guard.yaml' => "version: 1\n"]) {
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
        try {
            $command = new CheckCommand($project->root);
            $input = new \Symfony\Component\Console\Input\ArrayInput([], $command->getDefinition());
            $run = new \Guard\Cli\CheckRun();
            self::assertNull($run->baselinePath($project->root . '/guard.yaml', $input));
            $project->write('guard-baseline.json', '{"version":1,"entries":[]}');
            self::assertSame($project->root . '/guard-baseline.json', $run->baselinePath($project->root . '/guard.yaml', $input));
            $input->setOption('no-baseline', true);
            self::assertNull($run->baselinePath($project->root . '/guard.yaml', $input));
            $input->setOption('baseline', 'explicit.json');
            $this->expectException(PolicyException::class);
            $run->baselinePath($project->root . '/guard.yaml', $input);
        } finally {
            $project->remove();
        }
    }
}
