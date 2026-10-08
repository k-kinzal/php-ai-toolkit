<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use FilesystemIterator;
use JsonException;
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
#[\PHPUnit\Framework\Attributes\CoversClass(\Guard\Config\RuleCatalog::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\MetricPolicyReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\ApplyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\ApplyConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\ApplyPolicyUsageValidator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\ApplyRuleConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\ApplyRuleConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\ApplyRuleListConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\PolicyListConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\PolicyConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\PolicyDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\PolicyResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\PolicyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\LimitConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\LimitConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\MetricsConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\ScanConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Validation\MetricConfigKeyValidator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Validation\MetricConfigScalarReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Validation\MetricConfigStringListReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\FieldMessage::class)]
final class RuleCatalogTest extends \PHPUnit\Framework\TestCase
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
            $rules = (new \Guard\Config\RuleCatalog())->load($project->root . '/guard.yaml');
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
    public function testFieldsMarksReadOnlyFormatsAsManual(): void
    {
        $rules = (new \Guard\Config\RuleCatalog())->fields([['id' => 'x', 'file' => 'a.json5', 'select' => '/x', 'assert' => ['equals' => true]]]);
        self::assertFalse($rules[0]->fixable);
    }

    public function testMetricsShowsInheritedLimitsOnlyForUsedProfiles(): void
    {
        $rules = (new \Guard\Config\RuleCatalog())->metrics(['profiles' => [
            'base' => ['limits' => ['file' => ['lines' => 100, 'ncloc' => 80]]],
            'standard' => ['extends' => 'base', 'limits' => ['file' => ['lines' => null]]],
        ]], '/project');
        self::assertCount(1, $rules);
        self::assertSame('metrics.file_ncloc', $rules[0]->id);
        self::assertSame(80, $rules[0]->details['max']);
    }

    public function testStructurePreservesAnEmptyAllowConstraint(): void
    {
        $rules = (new \Guard\Config\RuleCatalog())->structure(['directories' => [['path' => 'src', 'allow' => [], 'forbid_empty' => false]]]);
        self::assertCount(1, $rules);
        self::assertSame('structure.disallowed_file', $rules[0]->id);
        self::assertSame([], $rules[0]->details['allow']);
    }

    public function testDocumentationIncludesDeclaredFilesAndScanRules(): void
    {
        $rules = (new \Guard\Config\RuleCatalog())->documentation(['files' => ['README.md' => ['headings' => []]], 'scan' => ['*.md']]);
        self::assertSame('documentation.missing_document', $rules[0]->id);
        self::assertSame('documentation.undeclared_document', $rules[count($rules) - 1]->id);
    }

}
