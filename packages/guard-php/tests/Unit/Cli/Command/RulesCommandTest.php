<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use FilesystemIterator;
use Guard\Cli\Command\GuardCommand;
use Guard\Cli\Command\RulesCommand;
use Guard\Cli\FormatDetector;
use Guard\Config\Configuration;
use Guard\Config\ConfigurationLoader;
use Guard\Config\DocumentMerger;
use Guard\Config\ImportResolver;
use Guard\Config\Reader\ExtensionConfigReader;
use Guard\Config\RuleCatalog;
use Guard\Config\RuleReader;
use Guard\Config\Schema;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Diagnostic\FieldMessage;
use Guard\Policy\Diagnostic\RuleDescription;
use Guard\Policy\Diagnostic\RuleMessages;
use Guard\Policy\FieldConstraints;
use Guard\Policy\PolicyBinding;
use Guard\Policy\Rule;
use Guard\Reporting\RuleReporter;
use Guard\Structure\Document\Constraint;
use Guard\Structure\Document\Selection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Tester\CommandTester;

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
#[CoversClass(RulesCommand::class)]
#[UsesClass(ConfigurationLoader::class)]
#[UsesClass(ImportResolver::class)]
#[UsesClass(DocumentMerger::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(RuleReader::class)]
#[UsesClass(ExtensionConfigReader::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(Rule::class)]
#[UsesClass(Constraint::class)]
#[UsesClass(FieldConstraints::class)]
#[UsesClass(Selection::class)]
#[UsesClass(RuleDescription::class)]
#[UsesClass(RuleMessages::class)]
#[UsesClass(GuardCommand::class)]
#[UsesClass(FormatDetector::class)]
#[UsesClass(RuleCatalog::class)]
#[UsesClass(RuleReporter::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(FieldMessage::class)]
final class RulesCommandTest extends TestCase
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
            $tester = new CommandTester(new RulesCommand($project->root));
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
