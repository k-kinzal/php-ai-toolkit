<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Tree;

use Guard\Config\Configuration;
use Guard\Execution\Context;
use Guard\Policy\PolicyException;
use Guard\Reporting\Finding;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Project;

/**
 * @covers \Guard\Policy\Tree\TreePolicy
 * @uses \Guard\Collect\Markdown\Filesystem\PathResolver
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Collect\Php\PhpSources
 * @uses \Guard\Collect\Php\SourceMetrics
 * @uses \Guard\Collect\Tree\DirectoryTree
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListing
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListingReader
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryTreeScanner
 * @uses \Guard\Collect\Tree\Filesystem\PathInclusionPolicy
 * @uses \Guard\Collect\Tree\Filesystem\PathResolver
 * @uses \Guard\Collect\Tree\TreeCollector
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
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
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
 * @uses \Guard\Policy\Tree\Violation
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Policy\Tree\TreePolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Filesystem\PathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\AtxHeadingMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\Heading::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\HeadingTextNormalizer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\PhpSources::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\SourceMetrics::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\DirectoryTree::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryListingReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryTreeScanner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\PathInclusionPolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\PathResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\TreeCollector::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Tree\Violation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Finding::class)]
final class TreePolicyTest extends TestCase
{
    /**
     * @throws JsonException

     * @throws \Nette\Neon\Exception
     */
    public function testEvaluateOverlappingRulesRunIndependentlyWithoutReadingTheTreeAgain(): void
    {
        $project = new Project(['src/one.php' => '<?php', 'guard.yaml' => "version: 1\nstructure:\n  paths: [src]\n  directories:\n    - {path: src, allow: ['*.txt']}\n    - {path: 'src/**', file_case: pascal}\n"]);
        try {
            $context = $project->context();
            $subject = (new \Guard\Collect\Tree\TreeCollector())->collect($context)[0];
            unlink($project->root . '/src/one.php');
            rmdir($project->root . '/src');
            $plan = (new \Guard\Policy\Tree\TreePolicy())->evaluate($subject, $context);
            self::assertSame(['structure.disallowed_file', 'structure.file_case'], array_map(static fn (Finding $finding): string => $finding->rule, $plan->findings));
        } finally {
            $project->remove();
        }
    }
    public function testRejectsInformationOfAnotherType(): void
    {
        $this->expectException(PolicyException::class);
        (new \Guard\Policy\Tree\TreePolicy())->evaluate(new \Guard\Collect\Php\PhpSources([], []), new Context(new Configuration('/none', null, null, null, []), '/none/guard.yaml', false));
    }

}
