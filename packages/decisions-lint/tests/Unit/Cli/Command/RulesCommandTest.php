<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use DecisionsLint\Cli\Command\LintCommand;
use DecisionsLint\Cli\Command\RulesCommand;
use DecisionsLint\Cli\FormatDetector;
use DecisionsLint\Config\Configuration;
use DecisionsLint\Config\ConfigurationLoader;
use DecisionsLint\Config\DocumentLoader;
use DecisionsLint\Config\DocumentMerger;
use DecisionsLint\Config\ImportResolver;
use DecisionsLint\Config\ProviderReader;
use DecisionsLint\Config\RuleReader;
use DecisionsLint\Config\SchemaCatalog;
use DecisionsLint\Config\SchemaValidator;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\ProviderSettings;
use DecisionsLint\Reporting\RuleCatalogReporter;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
use DecisionsLint\Rule\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \DecisionsLint\Cli\Command\RulesCommand
 * @uses \DecisionsLint\Cli\Command\LintCommand
 * @uses \DecisionsLint\Cli\FormatDetector
 * @uses \DecisionsLint\Config\Configuration
 * @uses \DecisionsLint\Config\ConfigurationLoader
 * @uses \DecisionsLint\Config\DocumentLoader
 * @uses \DecisionsLint\Config\DocumentMerger
 * @uses \DecisionsLint\Config\ImportResolver
 * @uses \DecisionsLint\Config\ProviderReader
 * @uses \DecisionsLint\Config\RuleReader
 * @uses \DecisionsLint\Config\SchemaCatalog
 * @uses \DecisionsLint\Config\SchemaValidator
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Provider\ProviderSettings
 * @uses \DecisionsLint\Reporting\RuleCatalogReporter
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Rule\Violation
 */
#[CoversClass(RulesCommand::class)]
#[UsesClass(LintCommand::class)]
#[UsesClass(FormatDetector::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
#[UsesClass(DocumentLoader::class)]
#[UsesClass(DocumentMerger::class)]
#[UsesClass(ImportResolver::class)]
#[UsesClass(ProviderReader::class)]
#[UsesClass(RuleReader::class)]
#[UsesClass(SchemaCatalog::class)]
#[UsesClass(SchemaValidator::class)]
#[UsesClass(LintException::class)]
#[UsesClass(ProviderSettings::class)]
#[UsesClass(RuleCatalogReporter::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Template::class)]
#[UsesClass(Violation::class)]
final class RulesCommandTest extends TestCase
{
    public function testExecuteListsTheEffectiveRules(): void
    {
        $root = sys_get_temp_dir() . '/decisions-rules-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/decisions.yaml', "version: 1\nrules:\n  r.one:\n    description: One.\n    severity: info\n    target: {kind: class}\n    decision: {type: predicate, instructions: 'Q?'}\n    violations: [{answer: true, message: m, fix: f}]\n");
        try {
            $tester = new CommandTester(new RulesCommand($root));
            self::assertSame(0, $tester->execute(['--format' => 'text']));
            self::assertSame("r.one  info  class predicate  enabled\n  One.\n1 rule.\n", $tester->getDisplay());
            self::assertSame(0, $tester->execute(['--format' => 'json']));
            self::assertStringContainsString('"count": 1', $tester->getDisplay());
            self::assertSame(LintCommand::INVALID, $tester->execute(['--config' => 'missing.yaml']));
            self::assertStringContainsString('Decisions error: Configuration not found:', $tester->getDisplay());
        } finally {
            unlink($root . '/decisions.yaml');
            rmdir($root);
        }
    }
}
