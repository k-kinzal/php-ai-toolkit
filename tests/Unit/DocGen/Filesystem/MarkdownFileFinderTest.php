<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Filesystem;

use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Filesystem\MarkdownFileFinder;
use PhpAiToolkit\DocGen\Filesystem\SourceFileFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Filesystem\MarkdownFileFinder
 * @uses \PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver
 * @uses \PhpAiToolkit\DocGen\Filesystem\SourceFileFinder
 */
#[CoversClass(MarkdownFileFinder::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(SourceFileFinder::class)]
final class MarkdownFileFinderTest extends TestCase
{
    public function testFindReturnsMarkdownFilesRecursivelySorted(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-md-' . uniqid('', true);
        mkdir($dir . '/docs/rules', 0777, true);
        file_put_contents($dir . '/README.md', '# Demo');
        file_put_contents($dir . '/docs/guide.md', '# Guide');
        file_put_contents($dir . '/docs/rules/Rule.md', '# Rule');
        file_put_contents($dir . '/docs/notes.txt', 'x');

        self::assertSame([
            $dir . '/README.md',
            $dir . '/docs/guide.md',
            $dir . '/docs/rules/Rule.md',
        ], (new MarkdownFileFinder())->find($dir, $dir, []));
    }

    public function testFindPrunesDependencyBuildAndHiddenDirectories(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-md-' . uniqid('', true);
        mkdir($dir . '/vendor/acme', 0777, true);
        mkdir($dir . '/build', 0777, true);
        mkdir($dir . '/.github', 0777, true);
        file_put_contents($dir . '/README.md', '# Demo');
        file_put_contents($dir . '/vendor/acme/README.md', '# Vendor');
        file_put_contents($dir . '/build/report.md', '# Report');
        file_put_contents($dir . '/.github/CONTRIBUTING.md', '# Contributing');

        self::assertSame([$dir . '/README.md'], (new MarkdownFileFinder())->find($dir, $dir, []));
    }

    public function testFindHonorsExcludeGlobsAndMissingDirectories(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-md-' . uniqid('', true);
        mkdir($dir . '/docs', 0777, true);
        file_put_contents($dir . '/README.md', '# Demo');
        file_put_contents($dir . '/docs/internal.md', '# Internal');

        self::assertSame([$dir . '/README.md'], (new MarkdownFileFinder())->find($dir, $dir, ['docs']));
        self::assertSame([], (new MarkdownFileFinder())->find($dir . '/absent', $dir, []));
    }

    public function testPrunedReportsDependencyBuildAndHiddenDirectories(): void
    {
        self::assertTrue((new MarkdownFileFinder())->pruned('vendor'));
        self::assertTrue((new MarkdownFileFinder())->pruned('node_modules'));
        self::assertTrue((new MarkdownFileFinder())->pruned('.git'));
        self::assertFalse((new MarkdownFileFinder())->pruned('docs'));
    }
}
