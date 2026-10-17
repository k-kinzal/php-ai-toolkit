<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use FilesystemIterator;
use Guard\Config\Configuration;
use Guard\Config\ConfigurationLoader;
use Guard\Config\DocumentMerger;
use Guard\Config\ImportResolver;
use Guard\Config\Profile\ApplyConfigReader;
use Guard\Config\Profile\ApplyPolicyUsageValidator;
use Guard\Config\Profile\ApplyRuleConfigReader;
use Guard\Config\Profile\ApplyRuleListConfigReader;
use Guard\Config\Profile\PolicyConfigReader;
use Guard\Config\Profile\PolicyDefinition;
use Guard\Config\Profile\PolicyListConfigReader;
use Guard\Config\Profile\PolicyResolver;
use Guard\Config\Reader\ExtensionConfigReader;
use Guard\Config\Reader\LimitConfigReader;
use Guard\Config\Reader\MetricPolicyReader;
use Guard\Config\RuleCatalog;
use Guard\Config\RuleReader;
use Guard\Config\Schema;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Config\Validation\MetricConfigStringListReader;
use Guard\Policy\Definition\ApplyConfig;
use Guard\Policy\Definition\ApplyRuleConfig;
use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Definition\MetricsConfig;
use Guard\Policy\Definition\PolicyConfig;
use Guard\Policy\Definition\ScanConfig;
use Guard\Policy\Diagnostic\FieldMessage;
use Guard\Policy\Diagnostic\RuleDescription;
use Guard\Policy\Diagnostic\RuleMessages;
use Guard\Policy\FieldConstraints;
use Guard\Policy\PolicyBinding;
use Guard\Policy\Rule;
use Guard\Structure\Document\Constraint;
use Guard\Structure\Document\Selection;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @covers \Guard\Config\RuleCatalog
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
 * @uses \Guard\Config\Reader\MetricPolicyReader
 * @uses \Guard\Policy\Definition\ApplyConfig
 * @uses \Guard\Config\Profile\ApplyConfigReader
 * @uses \Guard\Config\Profile\ApplyPolicyUsageValidator
 * @uses \Guard\Policy\Definition\ApplyRuleConfig
 * @uses \Guard\Config\Profile\ApplyRuleConfigReader
 * @uses \Guard\Config\Profile\ApplyRuleListConfigReader
 * @uses \Guard\Config\Profile\PolicyListConfigReader
 * @uses \Guard\Config\Profile\PolicyConfigReader
 * @uses \Guard\Config\Profile\PolicyDefinition
 * @uses \Guard\Config\Profile\PolicyResolver
 * @uses \Guard\Policy\Definition\PolicyConfig
 * @uses \Guard\Config\Reader\LimitConfigReader
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Definition\MetricsConfig
 * @uses \Guard\Policy\Definition\ScanConfig
 * @uses \Guard\Config\Validation\MetricConfigKeyValidator
 * @uses \Guard\Config\Validation\MetricConfigScalarReader
 * @uses \Guard\Config\Validation\MetricConfigStringListReader
 * @uses \Guard\Policy\Diagnostic\FieldMessage
 */
#[CoversClass(RuleCatalog::class)]
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
#[UsesClass(MetricPolicyReader::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyConfigReader::class)]
#[UsesClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(ApplyRuleConfigReader::class)]
#[UsesClass(ApplyRuleListConfigReader::class)]
#[UsesClass(PolicyListConfigReader::class)]
#[UsesClass(PolicyConfigReader::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(PolicyResolver::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricsConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(MetricConfigKeyValidator::class)]
#[UsesClass(MetricConfigScalarReader::class)]
#[UsesClass(MetricConfigStringListReader::class)]
#[UsesClass(FieldMessage::class)]
final class RuleCatalogTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testLoadResolvesOverridesWithoutOpeningTheTarget(): void
    {
        $project = new class (['base.yaml' => "configuration:\n  - {id: value, file: missing.json, select: /value, message: Original, assert: {equals: 1}}\n", 'guard.yaml' => "version: 1\nimports: [base.yaml]\nconfiguration:\n  - {id: value, message: 'Override {select} in {file} must {expectation}. {fix}', file: renamed.json, assert: {equals: 2}, level: recommended}\n"]) {
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
            $rules = (new RuleCatalog())->load($project->root . '/guard.yaml');
            self::assertCount(1, $rules);
            self::assertSame('Override /value in renamed.json must equal 2. Set /value in renamed.json to 2. Run guard fix to apply the configured repair.', $rules[0]->message);
            self::assertSame('recommended', $rules[0]->level);
            self::assertSame(['equals' => 2], $rules[0]->details['assert']);
        } finally {
            $project->remove();
        }
    }

    /**
     * @throws JsonException
     */
    public function testFieldsMarksEveryFormatWithARepairAsFixable(): void
    {
        $rules = (new RuleCatalog())->fields([['id' => 'x', 'file' => 'a.json5', 'select' => '/x', 'assert' => ['equals' => true]]]);
        self::assertTrue($rules[0]->fixable);
    }

    public function testMetricsShowsInheritedLimitsOnlyForUsedProfiles(): void
    {
        $rules = (new RuleCatalog())->metrics(['profiles' => [
            'base' => ['limits' => ['file' => ['lines' => 100, 'ncloc' => 80]]],
            'standard' => ['extends' => 'base', 'limits' => ['file' => ['lines' => null]]],
        ]], '/project');
        self::assertCount(1, $rules);
        self::assertSame('metrics.file_ncloc', $rules[0]->id);
        self::assertSame(80, $rules[0]->details['max']);
    }

    public function testStructurePreservesAnEmptyAllowConstraint(): void
    {
        $rules = (new RuleCatalog())->structure(['directories' => [['path' => 'src', 'allow' => [], 'forbid_empty' => false]]]);
        self::assertCount(1, $rules);
        self::assertSame('structure.disallowed_file', $rules[0]->id);
        self::assertSame([], $rules[0]->details['allow']);
    }

    public function testDocumentationIncludesDeclaredFilesAndScanRules(): void
    {
        $rules = (new RuleCatalog())->documentation(['files' => ['README.md' => ['headings' => []]], 'scan' => ['*.md']]);
        self::assertSame('documentation.missing_document', $rules[0]->id);
        self::assertSame('documentation.undeclared_document', $rules[count($rules) - 1]->id);
    }

}
