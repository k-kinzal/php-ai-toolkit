<?php

declare(strict_types=1);

namespace Tests\Unit\Execution;

use Guard\Collect\Subject;
use Guard\Config\Configuration;
use Guard\Execution\Context;
use Guard\Execution\Pipeline;
use Guard\Execution\Plan;
use Guard\Extension\Registry;
use Guard\Reporting\Finding;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Execution\Pipeline
 * @uses \Guard\Collect\Configuration\ConfigurationCollector
 * @uses \Guard\Collect\Configuration\ConfigurationDocument
 * @uses \Guard\Collect\Markdown\Filesystem\MarkdownFileFinder
 * @uses \Guard\Collect\Markdown\Filesystem\MarkdownFileReader
 * @uses \Guard\Collect\Markdown\Filesystem\PathResolver
 * @uses \Guard\Collect\Markdown\MarkdownCollector
 * @uses \Guard\Collect\Markdown\MarkdownDocuments
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\BlockLineScanner
 * @uses \Guard\Collect\Markdown\Parsing\BlockMarkerMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Fence
 * @uses \Guard\Collect\Markdown\Parsing\FenceMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingParser
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Collect\Markdown\Parsing\HtmlBlockMatcher
 * @uses \Guard\Collect\Markdown\Parsing\LineIndentation
 * @uses \Guard\Collect\Markdown\Parsing\LineScanner
 * @uses \Guard\Collect\Markdown\Parsing\MarkdownLineSplitter
 * @uses \Guard\Collect\Markdown\Parsing\ParserState
 * @uses \Guard\Collect\Markdown\Parsing\SetextUnderlineMatcher
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
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListing
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListingReader
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryTreeScanner
 * @uses \Guard\Collect\Tree\Filesystem\PathInclusionPolicy
 * @uses \Guard\Collect\Tree\Filesystem\PathResolver
 * @uses \Guard\Collect\Tree\TreeCollector
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Config\Doc\DocumentationConfig
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\MetricsConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\ScanConfig
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\StructureConfig
 * @uses \Guard\Document\DataDocument
 * @uses \Guard\Document\DocumentFailure
 * @uses \Guard\Document\DocumentNode
 * @uses \Guard\Document\Json5Reader
 * @uses \Guard\Document\PhpConfigReader
 * @uses \Guard\Document\PhpDocument
 * @uses \Guard\Document\Pointer
 * @uses \Guard\Document\Selection
 * @uses \Guard\Document\TomlEncoder
 * @uses \Guard\Document\XmlDocument
 * @uses \Guard\Execution\ChangeSet
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Execution\TargetPath
 * @uses \Guard\Extension\BuiltinExtension
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Extension\Registry
 * @uses \Guard\Policy\Configuration\ConfigurationPolicy
 * @uses \Guard\Policy\Constraint
 * @uses \Guard\Policy\Doc\DocPolicy
 * @uses \Guard\Policy\Doc\HeadingHunkClassifier
 * @uses \Guard\Policy\Doc\HeadingSequenceAligner
 * @uses \Guard\Policy\Doc\HeadingStructureComparator
 * @uses \Guard\Policy\Doc\Violation
 * @uses \Guard\Policy\Doc\ViolationFactory
 * @uses \Guard\Policy\Loc\Assignment\ApplyRuleMatcher
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssigner
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssignment
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \Guard\Policy\Loc\LocPolicy
 * @uses \Guard\Policy\Loc\Violation
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Policy\RuleEvaluator
 * @uses \Guard\Policy\Tree\CaseConventionMatcher
 * @uses \Guard\Policy\Tree\ChildCountInspector
 * @uses \Guard\Policy\Tree\DepthInspector
 * @uses \Guard\Policy\Tree\DirNameInspector
 * @uses \Guard\Policy\Tree\DirectoryPatternMatcher
 * @uses \Guard\Policy\Tree\DirectoryRuleInspector
 * @uses \Guard\Policy\Tree\EmptyDirectoryInspector
 * @uses \Guard\Policy\Tree\FileNameInspector
 * @uses \Guard\Policy\Tree\RequiredFileInspector
 * @uses \Guard\Policy\Tree\TotalFileCountInspector
 * @uses \Guard\Policy\Tree\TreePolicy
 * @uses \Guard\Policy\Tree\Violation
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(Pipeline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Configuration\ConfigurationCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Configuration\ConfigurationDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Filesystem\MarkdownFileFinder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Filesystem\MarkdownFileReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Filesystem\PathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\MarkdownCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\MarkdownDocuments::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\AtxHeadingMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\BlockLineScanner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\BlockMarkerMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\Fence::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\FenceMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\Heading::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\HeadingParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\HeadingTextNormalizer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\HtmlBlockMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\LineIndentation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\LineScanner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\MarkdownLineSplitter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\ParserState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\SetextUnderlineMatcher::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryListingReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryTreeScanner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\PathInclusionPolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\PathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\TreeCollector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Doc\DeclaredHeading::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Doc\DocumentConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Doc\DocumentationConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\LimitConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\MetricsConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\PolicyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\ScanConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Tree\RuleConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Tree\StructureConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\DataDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\DocumentFailure::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\DocumentNode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Json5Reader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\PhpConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\PhpDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Pointer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\TomlEncoder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\XmlDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\ChangeSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\TargetPath::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\BuiltinExtension::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Registry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Configuration\ConfigurationPolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Constraint::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\DocPolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\HeadingHunkClassifier::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\HeadingSequenceAligner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\HeadingStructureComparator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\Violation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\ViolationFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\ApplyRuleMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\FilePolicyAssigner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\FilePolicyAssignment::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\LocPolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Violation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\RuleEvaluator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\CaseConventionMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\ChildCountInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\DepthInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\DirNameInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\DirectoryPatternMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\DirectoryRuleInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\EmptyDirectoryInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\FileNameInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\RequiredFileInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\TotalFileCountInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\TreePolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\Violation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Finding::class)]
final class PipelineTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testRunCollectsOnceAndDispatchesOnlyMatchingSubjects(): void
    {
        $events = [];
        $subject = new \Guard\Collect\Tree\DirectoryTree([]);
        $registry = new Registry();
        $registry->addCollector('first', new \Tests\Support\CallbackCollector(static function (Context $context) use (&$events, $subject): array {
            $events[] = 'collect-first';
            return [$subject];
        }));
        $registry->addCollector('second', new \Tests\Support\CallbackCollector(static function (Context $context) use (&$events): array {
            $events[] = 'collect-second';
            return [new \Guard\Collect\Php\PhpSources([], [])];
        }));

        $registry->addPolicy('one', \Guard\Collect\Tree\DirectoryTree::class, new \Tests\Support\CallbackPolicy(static function (Subject $input, Context $context) use (&$events, $subject): Plan {
            self::assertSame($subject, $input);
            $events[] = 'one';
            return new Plan([new Finding('tree', 'one', 'recommended', 'Review one')], []);
        }));

        $registry->addPolicy('two', \Guard\Collect\Tree\DirectoryTree::class, new \Tests\Support\CallbackPolicy(static function (Subject $input, Context $context) use (&$events, $subject): Plan {
            self::assertSame($subject, $input);
            $events[] = 'two';
            return new Plan([new Finding('tree', 'two', 'recommended', 'Review two')], []);
        }));
        $context = new Context(new Configuration('/unread', null, null, null, []), '/unread/guard.yaml', false);
        $plan = (new Pipeline($registry))->run($context);
        self::assertSame(['collect-first', 'one', 'two', 'collect-second'], $events);
        self::assertSame(['one', 'two'], array_map(static fn (Finding $finding): string => $finding->rule, $plan->findings));
        self::assertSame([], $plan->changes);
        self::assertSame([], $plan->blockingFindings);
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testRunMergesBlockingFindingsWithoutTreatingSourceViolationsAsWriteBlockers(): void
    {
        $registry = new Registry();
        $registry->addCollector('tree', new \Tests\Support\CallbackCollector(static fn (Context $context): array => [new \Guard\Collect\Tree\DirectoryTree([])]));
        $source = new Finding('src', 'source', 'required', 'Fix source');
        $blocked = new Finding('config', 'conflict', 'required', 'Resolve conflict');
        $registry->addPolicy('source', Subject::class, new \Tests\Support\CallbackPolicy(static fn (Subject $subject, Context $context): Plan => new Plan([$source], [])));
        $registry->addPolicy('repair', Subject::class, new \Tests\Support\CallbackPolicy(static fn (Subject $subject, Context $context): Plan => new Plan([$blocked], [], [$blocked])));
        $plan = (new Pipeline($registry))->run(new Context(new Configuration('/unread', null, null, null, []), '/unread/guard.yaml', true));
        self::assertSame([$source, $blocked], $plan->findings);
        self::assertSame([$blocked], $plan->blockingFindings);
    }

}
