<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @covers \Guard\Cli\Command\RulesCommand
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\Schema
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Config\Reader\ExtensionConfigReader
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Structure\Document\Constraint
 * @uses \Guard\Policy\FieldConstraints
 * @uses \Guard\Structure\Document\Selection
 * @uses \Guard\Policy\Diagnostic\RuleDescription
 * @uses \Guard\Policy\Diagnostic\RuleMessages
 * @uses \Guard\Cli\Command\GuardCommand
 * @uses \Guard\Cli\FormatDetector
 * @uses \Guard\Config\RuleCatalog
 * @uses \Guard\Reporting\RuleReporter
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Policy\Diagnostic\FieldMessage
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Guard\Cli\Command\RulesCommand::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\ImportResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\DocumentMerger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\RuleReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\ExtensionConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\Constraint::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FieldConstraints::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\RuleDescription::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\RuleMessages::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Cli\Command\GuardCommand::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Cli\FormatDetector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\RuleCatalog::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\RuleReporter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\FieldMessage::class)]
final class RulesCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunFiltersTheResolvedRulesAndRejectsEmptyQueries(): void
    {
        $project = new class (['guard.yaml' => "version: 1\nconfiguration:\n  - {id: workers, file: app.json, select: /workers, message: Bound concurrency. Set /workers to 2., assert: {equals: 2}}\n"]) {
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
            $tester = new \Symfony\Component\Console\Tester\CommandTester(new \Guard\Cli\Command\RulesCommand($project->root));
            self::assertSame(0, $tester->execute(['--query' => 'CONCURRENCY', '--format' => 'text']));
            self::assertStringContainsString('workers', $tester->getDisplay());
            self::assertStringContainsString('Bound concurrency', $tester->getDisplay());
            self::assertSame(0, $tester->execute(['--query' => 'missing', '--format' => 'json']));
            self::assertStringContainsString('"rules": []', $tester->getDisplay());
            self::assertSame(2, $tester->execute(['--query' => '']));
        } finally {
            $project->remove();
        }
    }

}
