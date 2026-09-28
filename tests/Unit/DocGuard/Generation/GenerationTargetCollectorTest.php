<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Generation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;
use Toolkit\DocGuard\Filesystem\MarkdownFileFinder;
use Toolkit\DocGuard\Generation\GenerationTargetCollector;

/**
 * @covers \Toolkit\DocGuard\Generation\GenerationTargetCollector
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Filesystem\MarkdownFileFinder
 */
#[CoversClass(GenerationTargetCollector::class)]
#[UsesClass(DocGuardException::class)]
#[UsesClass(DocGuardPathResolver::class)]
#[UsesClass(MarkdownFileFinder::class)]
final class GenerationTargetCollectorTest extends TestCase
{
    public function testCollectDefaultsToRootAndDocsDocuments(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-targets-' . uniqid('', true);
        mkdir($dir . '/docs/extensions', 0777, true);
        touch($dir . '/README.md');
        touch($dir . '/AGENTS.md');
        touch($dir . '/docs/guide.md');
        touch($dir . '/docs/extensions/pdo.md');

        self::assertSame(
            ['documents' => ['AGENTS.md', 'README.md', 'docs/extensions/pdo.md', 'docs/guide.md'], 'scan' => ['*.md', 'docs/**/*.md']],
            (new GenerationTargetCollector())->collect($dir, []),
        );
    }

    public function testCollectScansOnlyTheRootWithoutDocsDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-targets-' . uniqid('', true);
        mkdir($dir);
        touch($dir . '/README.md');

        self::assertSame(['documents' => ['README.md'], 'scan' => ['*.md']], (new GenerationTargetCollector())->collect($dir, []));
    }

    public function testCollectDeclaresGivenFilesAndDirectories(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-targets-' . uniqid('', true);
        mkdir($dir . '/manual', 0777, true);
        touch($dir . '/README.md');
        touch($dir . '/NOTES.md');
        touch($dir . '/manual/intro.md');

        self::assertSame(
            ['documents' => ['README.md', 'manual/intro.md'], 'scan' => ['manual/**/*.md']],
            (new GenerationTargetCollector())->collect($dir, ['./README.md', 'manual/', 'README.md']),
        );
    }

    public function testCollectRejectsMissingPaths(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-targets-' . uniqid('', true);
        mkdir($dir);

        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Cannot generate a DocGuard config for GUIDE.md: no such file or directory.');

        (new GenerationTargetCollector())->collect($dir, ['GUIDE.md']);
    }
}
