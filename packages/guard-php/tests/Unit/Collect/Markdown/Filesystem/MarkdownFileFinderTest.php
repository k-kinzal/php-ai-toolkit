<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown\Filesystem;

use Guard\Collect\Markdown\Filesystem\MarkdownFileFinder;
use Guard\Collect\Markdown\Filesystem\PathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\Filesystem\MarkdownFileFinder
 * @uses \Guard\Collect\Markdown\Filesystem\PathResolver
 */
#[CoversClass(MarkdownFileFinder::class)]
#[UsesClass(PathResolver::class)]
final class MarkdownFileFinderTest extends TestCase
{
    public function testFindMatchesSegmentAwarePatterns(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-find-' . uniqid('', true);
        mkdir($dir . '/docs/extensions', 0777, true);
        mkdir($dir . '/.github', 0777, true);
        mkdir($dir . '/packages/a', 0777, true);
        touch($dir . '/README.md');
        touch($dir . '/composer.json');
        touch($dir . '/.hidden.md');
        touch($dir . '/docs/guide.md');
        touch($dir . '/docs/extensions/pdo.md');
        touch($dir . '/.github/notes.md');
        touch($dir . '/packages/a/README.md');

        self::assertSame(['README.md'], (new MarkdownFileFinder())->find($dir, ['*.md']));
        self::assertSame(['docs/extensions/pdo.md', 'docs/guide.md'], (new MarkdownFileFinder())->find($dir, ['docs/**/*.md']));
        self::assertSame(['docs/guide.md'], (new MarkdownFileFinder())->find($dir, ['./docs/*.md', 'docs/guide.md']));
        self::assertSame(['.github/notes.md'], (new MarkdownFileFinder())->find($dir, ['.github/*.md']));
        self::assertSame([], (new MarkdownFileFinder())->find($dir, ['missing/**/*.md']));
    }

    public function testWalkDescendsThroughDoubleStarWithoutHiddenDirectories(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-walk-' . uniqid('', true);
        mkdir($dir . '/a/b', 0777, true);
        mkdir($dir . '/.git', 0777, true);
        touch($dir . '/top.md');
        touch($dir . '/a/b/deep.md');
        touch($dir . '/.git/ignored.md');

        $found = (new MarkdownFileFinder())->walk($dir, '', ['**', '*.md']);
        sort($found);

        self::assertSame(['a/b/deep.md', 'top.md'], $found);
        self::assertSame([], (new MarkdownFileFinder())->walk($dir, '', []));
    }
}
