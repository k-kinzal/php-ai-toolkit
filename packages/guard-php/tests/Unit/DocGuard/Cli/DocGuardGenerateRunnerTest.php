<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Cli\DocGuardGenerateRunner;
use Toolkit\DocGuard\Cli\DocGuardOutputWriter;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;
use Toolkit\DocGuard\Filesystem\MarkdownFileFinder;
use Toolkit\DocGuard\Filesystem\MarkdownFileReader;
use Toolkit\DocGuard\Generation\ConfigGenerator;
use Toolkit\DocGuard\Generation\ConfigYamlWriter;
use Toolkit\DocGuard\Generation\GenerationTargetCollector;
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
 * @covers \Toolkit\DocGuard\Cli\DocGuardGenerateRunner
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\BlockLineScanner
 * @uses \Toolkit\DocGuard\Markdown\BlockMarkerMatcher
 * @uses \Toolkit\DocGuard\Generation\ConfigGenerator
 * @uses \Toolkit\DocGuard\Generation\ConfigYamlWriter
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Cli\DocGuardOutputWriter
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Markdown\Fence
 * @uses \Toolkit\DocGuard\Markdown\FenceMatcher
 * @uses \Toolkit\DocGuard\Generation\GenerationTargetCollector
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingParser
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\DocGuard\Markdown\HtmlBlockMatcher
 * @uses \Toolkit\DocGuard\Markdown\LineIndentation
 * @uses \Toolkit\DocGuard\Markdown\LineScanner
 * @uses \Toolkit\DocGuard\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGuard\Filesystem\MarkdownFileReader
 * @uses \Toolkit\DocGuard\Markdown\MarkdownLineSplitter
 * @uses \Toolkit\DocGuard\Markdown\ParserState
 * @uses \Toolkit\DocGuard\Markdown\SetextUnderlineMatcher
 */
#[CoversClass(DocGuardGenerateRunner::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockLineScanner::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(ConfigGenerator::class)]
#[UsesClass(ConfigYamlWriter::class)]
#[UsesClass(DocGuardException::class)]
#[UsesClass(DocGuardOutputWriter::class)]
#[UsesClass(DocGuardPathResolver::class)]
#[UsesClass(Fence::class)]
#[UsesClass(FenceMatcher::class)]
#[UsesClass(GenerationTargetCollector::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingParser::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(HtmlBlockMatcher::class)]
#[UsesClass(LineIndentation::class)]
#[UsesClass(LineScanner::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(MarkdownFileReader::class)]
#[UsesClass(MarkdownLineSplitter::class)]
#[UsesClass(ParserState::class)]
#[UsesClass(SetextUnderlineMatcher::class)]
final class DocGuardGenerateRunnerTest extends TestCase
{
    public function testRunPrintsGeneratedConfig(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-generate-runner-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/README.md', "# Tool\n");
        $output = '';
        $writer = new DocGuardOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, (new DocGuardGenerateRunner($dir, new ConfigGenerator(), $writer))->run([]));
        self::assertStringContainsString("  'README.md':\n    headings:\n      - '# Tool'\n", $output);
    }

    public function testRunReturnsTwoOnGenerationError(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-generate-runner-' . uniqid('', true);
        mkdir($dir);
        $error = '';
        $writer = new DocGuardOutputWriter(null, static function (string $message) use (&$error): void {
            $error .= $message;
        });

        self::assertSame(2, (new DocGuardGenerateRunner($dir, new ConfigGenerator(), $writer))->run(['missing.md']));
        self::assertStringStartsWith('DocGuard error: Cannot generate a DocGuard config for missing.md', $error);
    }
}
