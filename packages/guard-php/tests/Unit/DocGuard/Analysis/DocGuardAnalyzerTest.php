<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Analysis\DocGuardAnalyzer;
use Toolkit\DocGuard\Analysis\HeadingHunkClassifier;
use Toolkit\DocGuard\Analysis\HeadingSequenceAligner;
use Toolkit\DocGuard\Analysis\HeadingStructureComparator;
use Toolkit\DocGuard\Analysis\UndeclaredDocumentInspector;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Analysis\ViolationFactory;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Config\DocGuardConfig;
use Toolkit\DocGuard\Config\DocumentConfig;
use Toolkit\DocGuard\Config\ReportConfig;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;
use Toolkit\DocGuard\Filesystem\MarkdownFileFinder;
use Toolkit\DocGuard\Filesystem\MarkdownFileReader;
use Toolkit\DocGuard\Markdown\AtxHeadingMatcher;
use Toolkit\DocGuard\Markdown\BlockLineScanner;
use Toolkit\DocGuard\Markdown\BlockMarkerMatcher;
use Toolkit\DocGuard\Markdown\Fence;
use Toolkit\DocGuard\Markdown\FenceMatcher;
use Toolkit\DocGuard\Markdown\Heading;
use Toolkit\DocGuard\Markdown\HeadingParser;
use Toolkit\DocGuard\Markdown\HeadingTextNormalizer;
use Toolkit\DocGuard\Markdown\HtmlBlockMatcher;
use Toolkit\DocGuard\Markdown\LineIndentation;
use Toolkit\DocGuard\Markdown\LineScanner;
use Toolkit\DocGuard\Markdown\MarkdownLineSplitter;
use Toolkit\DocGuard\Markdown\ParserState;
use Toolkit\DocGuard\Markdown\SetextUnderlineMatcher;

/**
 * @covers \Toolkit\DocGuard\Analysis\DocGuardAnalyzer
 * @uses \Toolkit\DocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\BlockLineScanner
 * @uses \Toolkit\DocGuard\Markdown\BlockMarkerMatcher
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Markdown\Fence
 * @uses \Toolkit\DocGuard\Markdown\FenceMatcher
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Analysis\HeadingHunkClassifier
 * @uses \Toolkit\DocGuard\Markdown\HeadingParser
 * @uses \Toolkit\DocGuard\Analysis\HeadingSequenceAligner
 * @uses \Toolkit\DocGuard\Analysis\HeadingStructureComparator
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\DocGuard\Markdown\HtmlBlockMatcher
 * @uses \Toolkit\DocGuard\Markdown\LineIndentation
 * @uses \Toolkit\DocGuard\Markdown\LineScanner
 * @uses \Toolkit\DocGuard\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGuard\Filesystem\MarkdownFileReader
 * @uses \Toolkit\DocGuard\Markdown\MarkdownLineSplitter
 * @uses \Toolkit\DocGuard\Markdown\ParserState
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Markdown\SetextUnderlineMatcher
 * @uses \Toolkit\DocGuard\Analysis\UndeclaredDocumentInspector
 * @uses \Toolkit\DocGuard\Analysis\Violation
 * @uses \Toolkit\DocGuard\Analysis\ViolationFactory
 */
#[CoversClass(DocGuardAnalyzer::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockLineScanner::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocGuardConfig::class)]
#[UsesClass(DocGuardException::class)]
#[UsesClass(DocGuardPathResolver::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(Fence::class)]
#[UsesClass(FenceMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingHunkClassifier::class)]
#[UsesClass(HeadingParser::class)]
#[UsesClass(HeadingSequenceAligner::class)]
#[UsesClass(HeadingStructureComparator::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(HtmlBlockMatcher::class)]
#[UsesClass(LineIndentation::class)]
#[UsesClass(LineScanner::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(MarkdownFileReader::class)]
#[UsesClass(MarkdownLineSplitter::class)]
#[UsesClass(ParserState::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(SetextUnderlineMatcher::class)]
#[UsesClass(UndeclaredDocumentInspector::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFactory::class)]
final class DocGuardAnalyzerTest extends TestCase
{
    public function testAnalyzePassesWhenOnlyContentChanged(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-analyze-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/README.md', "Tool\n====\n\nRewritten introduction.\n\n## Usage ##\n\n```bash\n## not a section\n```\n");
        $config = new DocGuardConfig(
            $dir,
            'doc-guard.yaml',
            [new DocumentConfig('README.md', [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Usage')], 6)],
            ['*.md'],
            new ReportConfig('ai', ['path']),
        );

        $result = (new DocGuardAnalyzer())->analyze($config);

        self::assertFalse($result->hasViolations());
        self::assertSame(1, $result->documents);
        self::assertSame(2, $result->headings);
    }

    public function testAnalyzeReportsStructureChangesAndMissingDocuments(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-analyze-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/README.md', "# Tool\n\n## Usage\n\n## Development\n");
        $config = new DocGuardConfig(
            $dir,
            'doc-guard.yaml',
            [
                new DocumentConfig('README.md', [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Usage')], 6),
                new DocumentConfig('docs/guide.md', [], 6),
            ],
            [],
            new ReportConfig('ai', ['path']),
        );

        $result = (new DocGuardAnalyzer())->analyze($config);

        self::assertSame(
            [['README.md', 'unexpected_heading'], ['docs/guide.md', 'missing_document']],
            array_map(static fn (Violation $violation): array => [$violation->path, $violation->rule], $result->violations),
        );
    }

    public function testAnalyzeIgnoresHeadingsDeeperThanMaxLevel(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-analyze-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/spec.md', "# Spec\n\n## SELECT\n\n### New clause\n");
        $config = new DocGuardConfig(
            $dir,
            'doc-guard.yaml',
            [new DocumentConfig('spec.md', [new DeclaredHeading(1, 'Spec'), new DeclaredHeading(2, 'SELECT')], 2)],
            [],
            new ReportConfig('ai', ['path']),
        );

        $result = (new DocGuardAnalyzer())->analyze($config);

        self::assertFalse($result->hasViolations());
        self::assertSame(2, $result->headings);
    }
}
