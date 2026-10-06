<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Loc;

use Guard\Config\Configuration;
use Guard\Execution\Context;
use Guard\Policy\PolicyException;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Project;

/**
 * @covers \Guard\Policy\Loc\LocPolicy
 * @uses \Guard\Collect\Markdown\Filesystem\PathResolver
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetricCollector
 * @uses \Guard\Collect\Php\Complexity\CyclomaticComplexityCalculator
 * @uses \Guard\Collect\Php\Complexity\CyclomaticComplexityState
 * @uses \Guard\Collect\Php\Complexity\CyclomaticDecisionWeight
 * @uses \Guard\Collect\Php\FileMetric\FileMetric
 * @uses \Guard\Collect\Php\Filesystem\FilePathPatternMatcher
 * @uses \Guard\Collect\Php\Filesystem\PathResolver
 * @uses \Guard\Collect\Php\Filesystem\PhpFileFinder
 * @uses \Guard\Collect\Php\Filesystem\PhpFileInclusionPolicy
 * @uses \Guard\Collect\Php\Filesystem\PhpPathFileCollector
 * @uses \Guard\Collect\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Collect\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Collect\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricCollector
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricLineCollector
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Collect\Php\FunctionMetric\NestedFunctionMetricRange
 * @uses \Guard\Collect\Php\PhpCollector
 * @uses \Guard\Collect\Php\PhpSources
 * @uses \Guard\Collect\Php\SourceMetricReader
 * @uses \Guard\Collect\Php\SourceMetrics
 * @uses \Guard\Collect\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Collect\Php\Token\CodeTokenLineResolver
 * @uses \Guard\Collect\Php\Token\PhpTokenNavigator
 * @uses \Guard\Collect\Php\Token\TokenLineCounter
 * @uses \Guard\Collect\Tree\DirectoryTree
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\Doc\ConfigKeyValidator
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DeclaredHeadingReader
 * @uses \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Config\Doc\DocumentConfigReader
 * @uses \Guard\Config\Doc\DocumentListConfigReader
 * @uses \Guard\Config\Doc\DocumentationConfig
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\DocumentationReader
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Config\Loc\ConfigKeyValidator
 * @uses \Guard\Config\Loc\ConfigScalarReader
 * @uses \Guard\Config\Loc\ConfigStringListReader
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\LimitConfigReader
 * @uses \Guard\Config\Loc\MetricsConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfigReader
 * @uses \Guard\Config\Loc\Policy\ApplyPolicyUsageValidator
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfigReader
 * @uses \Guard\Config\Loc\Policy\ApplyRuleListConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyDefinition
 * @uses \Guard\Config\Loc\Policy\PolicyListConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyResolver
 * @uses \Guard\Config\Loc\ScanConfig
 * @uses \Guard\Config\MetricsReader
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Config\Schema
 * @uses \Guard\Config\StructureReader
 * @uses \Guard\Config\Tree\ConfigScalarReader
 * @uses \Guard\Config\Tree\ConfigStringListReader
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\RuleConfigReader
 * @uses \Guard\Config\Tree\RuleListConfigReader
 * @uses \Guard\Config\Tree\StructureConfig
 * @uses \Guard\Document\Selection
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\Constraint
 * @uses \Guard\Policy\Loc\Assignment\ApplyRuleMatcher
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssigner
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssignment
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \Guard\Policy\Loc\Violation
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Policy\Loc\LocPolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Filesystem\PathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\AtxHeadingMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\Heading::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\HeadingTextNormalizer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeMetricCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Complexity\CyclomaticComplexityCalculator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Complexity\CyclomaticComplexityState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Complexity\CyclomaticDecisionWeight::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FileMetric\FileMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Filesystem\FilePathPatternMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Filesystem\PathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Filesystem\PhpFileFinder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Filesystem\PhpFileInclusionPolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Filesystem\PhpPathFileCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\ArrowExpressionBoundary::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\ArrowFunctionMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\BlockFunctionMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionBodyLocator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetricCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetricComplexityAssigner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetricLineCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionNameReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionScanState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\NestedFunctionMetricRange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\PhpCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\PhpSources::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\SourceMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\SourceMetrics::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Token\ClassLikeTokenMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Token\CodeTokenLineResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Token\PhpTokenNavigator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Token\TokenLineCounter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\DirectoryTree::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Doc\ConfigKeyValidator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Doc\DeclaredHeading::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Doc\DeclaredHeadingReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Doc\DocumentConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Doc\DocumentConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Doc\DocumentListConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Doc\DocumentationConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\DocumentMerger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\DocumentationReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\ImportResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\ConfigKeyValidator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\ConfigScalarReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\ConfigStringListReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\LimitConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\LimitConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\MetricsConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyPolicyUsageValidator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyRuleListConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\PolicyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\PolicyConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\PolicyDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\PolicyListConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\PolicyResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\ScanConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\MetricsReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\RuleReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\StructureReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Tree\ConfigScalarReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Tree\ConfigStringListReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Tree\RuleConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Tree\RuleConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Tree\RuleListConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Tree\StructureConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Constraint::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\ApplyRuleMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\FilePolicyAssigner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\FilePolicyAssignment::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Violation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class LocPolicyTest extends TestCase
{
    /**
     * @throws JsonException

     * @throws \Nette\Neon\Exception
     */
    public function testEvaluateProfilesApplyToCollectedMetricsAfterSourceFilesAreRemoved(): void
    {
        $project = new Project(['src/A.php' => "<?php\n\n\n", 'src/Native.php' => "<?php\n\n\n", 'guard.yaml' => "version: 1\nmetrics:\n  source: [src]\n  profiles:\n    standard: {limits: {file: {lines: 1}}}\n    native: {extends: standard, limits: {file: {lines: 10}}}\n  default: standard\n  assignments: [{name: native, match: {paths: [src/Native.php]}, policy: native}]\n"]);
        try {
            $context = $project->context();
            $subject = (new \Guard\Collect\Php\PhpCollector())->collect($context)[0];
            unlink($project->root . '/src/A.php');
            unlink($project->root . '/src/Native.php');
            $plan = (new \Guard\Policy\Loc\LocPolicy())->evaluate($subject, $context);
            self::assertCount(1, $plan->findings);
            self::assertSame('src/A.php', $plan->findings[0]->path);
            self::assertSame('metrics.file_lines', $plan->findings[0]->rule);
            self::assertSame([], $plan->blockingFindings);
        } finally {
            $project->remove();
        }
    }
    public function testRejectsInformationOfAnotherType(): void
    {
        $this->expectException(PolicyException::class);
        (new \Guard\Policy\Loc\LocPolicy())->evaluate(new \Guard\Collect\Tree\DirectoryTree([]), new Context(new Configuration('/none', null, null, null, []), '/none/guard.yaml', false));
    }


    public function testViolationsAllowsMetricsEqualToTheConfiguredLimit(): void
    {
        $metrics = (new \Guard\Collect\Php\SourceMetricReader())->parse("<?php\necho 1;\n", 'memory.php');
        self::assertSame([], (new \Guard\Policy\Loc\LocPolicy())->violations($metrics, \Guard\Config\Loc\LimitConfig::fromValues(['file.lines' => 2, 'file.ncloc' => 1])));
    }

}
