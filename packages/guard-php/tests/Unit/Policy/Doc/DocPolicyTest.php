<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Doc;

use Guard\Config\Configuration;
use Guard\Execution\Context;
use Guard\Policy\PolicyException;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Project;

/**
 * @covers \Guard\Policy\Doc\DocPolicy
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
 * @uses \Guard\Policy\Doc\HeadingHunkClassifier
 * @uses \Guard\Policy\Doc\HeadingSequenceAligner
 * @uses \Guard\Policy\Doc\HeadingStructureComparator
 * @uses \Guard\Policy\Doc\Violation
 * @uses \Guard\Policy\Doc\ViolationFactory
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Policy\Doc\DocPolicy::class)]
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
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\HeadingHunkClassifier::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\HeadingSequenceAligner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\HeadingStructureComparator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\Violation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Doc\ViolationFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class DocPolicyTest extends TestCase
{
    /**
     * @throws JsonException

     * @throws \Nette\Neon\Exception
     */
    public function testEvaluateChecksTheSnapshotAndFiltersDepthOnlyWhenApplyingThePolicy(): void
    {
        $project = new Project(['README.md' => "Project\n=======\n\n### Added detail\n", 'extra.md' => '# Extra', 'guard.yaml' => "version: 1\ndocumentation:\n  files:\n    README.md: {headings: ['# Project'], max_level: 1}\n  scan: ['*.md', '**/*.md']\n"]);
        try {
            $context = $project->context();
            $subject = (new \Guard\Collect\Markdown\MarkdownCollector())->collect($context)[0];
            unlink($project->root . '/README.md');
            unlink($project->root . '/extra.md');
            $plan = (new \Guard\Policy\Doc\DocPolicy())->evaluate($subject, $context);
            self::assertCount(1, $plan->findings);
            self::assertSame('documentation.undeclared_document', $plan->findings[0]->rule);
            self::assertSame('extra.md', $plan->findings[0]->path);
        } finally {
            $project->remove();
        }
    }
    public function testRejectsInformationOfAnotherType(): void
    {
        $this->expectException(PolicyException::class);
        (new \Guard\Policy\Doc\DocPolicy())->evaluate(new \Guard\Collect\Tree\DirectoryTree([]), new Context(new Configuration('/none', null, null, null, []), '/none/guard.yaml', false));
    }


    public function testUndeclaredRespectsExclusionsAndReportsTheFirstMatchingPatternOnce(): void
    {
        $config = new \Guard\Config\Doc\DocumentationConfig('/not-read', 'guard.yaml', [new \Guard\Config\Doc\DocumentConfig('README.md', [], 6)], [], []);
        $subject = new \Guard\Collect\Markdown\MarkdownDocuments([], ['skip.md'], [['pattern' => '*.md', 'paths' => ['README.md', 'extra.md', 'skip.md']], ['pattern' => '**/*.md', 'paths' => ['extra.md']]]);
        $violations = (new \Guard\Policy\Doc\DocPolicy())->undeclared($subject, $config);
        self::assertCount(1, $violations);
        self::assertSame('extra.md', $violations[0]->path);
        self::assertStringContainsString('*.md', $violations[0]->message);
    }

}
