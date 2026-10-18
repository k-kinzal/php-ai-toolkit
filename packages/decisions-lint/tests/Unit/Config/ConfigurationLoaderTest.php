<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

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
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
use DecisionsLint\Rule\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Config\ConfigurationLoader
 * @uses \DecisionsLint\Config\Configuration
 * @uses \DecisionsLint\Config\DocumentLoader
 * @uses \DecisionsLint\Config\DocumentMerger
 * @uses \DecisionsLint\Config\ImportResolver
 * @uses \DecisionsLint\Config\ProviderReader
 * @uses \DecisionsLint\Config\RuleReader
 * @uses \DecisionsLint\Config\SchemaCatalog
 * @uses \DecisionsLint\Config\SchemaValidator
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Provider\ProviderSettings
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Rule\Violation
 */
#[CoversClass(ConfigurationLoader::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(DocumentLoader::class)]
#[UsesClass(DocumentMerger::class)]
#[UsesClass(ImportResolver::class)]
#[UsesClass(ProviderReader::class)]
#[UsesClass(RuleReader::class)]
#[UsesClass(SchemaCatalog::class)]
#[UsesClass(SchemaValidator::class)]
#[UsesClass(LintException::class)]
#[UsesClass(ProviderSettings::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Template::class)]
#[UsesClass(Violation::class)]
final class ConfigurationLoaderTest extends TestCase
{
    public function testLoadReadsTheShippedRuleFilesThroughImports(): void
    {
        $root = sys_get_temp_dir() . '/decisions-loader-' . uniqid();
        mkdir($root);
        $rules = dirname(__DIR__, 3) . '/rules';
        file_put_contents($root . '/decisions.yaml', "version: 1\nprovider: {name: openai}\npaths: [app]\nexclude: ['app/Generated/**']\nfail_on: warning\nimports:\n  - $rules/cohesion.yaml\n  - $rules/ddd.yaml\n  - $rules/naming.yaml\nrules:\n  cohesion.method-belongs-to-class: {severity: error, decision: {threshold: 0.9}}\n  naming.method-says-what-it-does: {enabled: false}\n");
        try {
            $configuration = (new ConfigurationLoader(new ImportResolver(), new RuleReader(), new ProviderReader()))->load($root . '/decisions.yaml');
            self::assertSame('openai', $configuration->provider->name);
            self::assertSame(['app'], $configuration->paths);
            self::assertSame(['app/Generated/**'], $configuration->exclude);
            self::assertSame('warning', $configuration->failOn);
            self::assertSame(['cohesion.method-belongs-to-class', 'cohesion.property-belongs-to-class', 'ddd.usecase-domain-leak', 'ddd.repository-business-rule', 'naming.method-says-what-it-does'], array_map(static fn (Rule $rule): string => $rule->id, $configuration->rules));
            self::assertSame('error', $configuration->rules[0]->severity);
            self::assertSame(0.9, $configuration->rules[0]->decision->threshold);
            self::assertStringContainsString('{class}', $configuration->rules[0]->decision->instructions);
            self::assertCount(4, $configuration->activeRules());
        } finally {
            unlink($root . '/decisions.yaml');
            rmdir($root);
        }
    }

    public function testLoadFallsBackToSrcAndErrorWithoutPathsOrFailOn(): void
    {
        $root = sys_get_temp_dir() . '/decisions-loader-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/decisions.yaml', "version: 1\n");
        try {
            $configuration = (new ConfigurationLoader())->load($root . '/decisions.yaml');
            self::assertSame(['src'], $configuration->paths);
            self::assertSame('error', $configuration->failOn);
            self::assertSame('jev', $configuration->provider->name);
            self::assertSame([], $configuration->rules);
        } finally {
            unlink($root . '/decisions.yaml');
            rmdir($root);
        }
    }

    public function testLoadRejectsAnOverrideOfARuleNobodyDefined(): void
    {
        $root = sys_get_temp_dir() . '/decisions-loader-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/decisions.yaml', "version: 1\nrules:\n  ghost.rule: {severity: info}\n");
        try {
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('Rule "ghost.rule" (decisions.yaml) has no target.');
            (new ConfigurationLoader())->load($root . '/decisions.yaml');
        } finally {
            unlink($root . '/decisions.yaml');
            rmdir($root);
        }
    }

    public function testMappingReadsMappingsAndIgnoresOtherValues(): void
    {
        self::assertSame(['a' => 1], (new ConfigurationLoader())->mapping(['a' => 1]));
        self::assertSame([], (new ConfigurationLoader())->mapping(null));
    }

    public function testStringsKeepsOnlyStrings(): void
    {
        self::assertSame(['src'], (new ConfigurationLoader())->strings(['src', 1]));
    }
}
