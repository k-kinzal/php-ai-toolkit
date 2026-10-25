<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use Guard\Collect\Filesystem\GitTrackedFiles;
use Guard\Collect\Filesystem\QueryResult;
use Guard\Collect\Matching\ScopeMatcher;
use Guard\Collect\Matching\SelectionFilter;
use Guard\Diagnostic\PolicyException;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\Path;
use Guard\Input\PathPatternMatcher;
use Guard\Input\Scope;
use Guard\Input\Selection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @covers \Guard\Collect\Filesystem\GitTrackedFiles
 * @uses \Guard\Collect\Filesystem\QueryResult
 * @uses \Guard\Collect\Matching\ScopeMatcher
 * @uses \Guard\Collect\Matching\SelectionFilter
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\Path
 * @uses \Guard\Input\PathPatternMatcher
 * @uses \Guard\Input\Scope
 * @uses \Guard\Input\Selection
 * @medium
 */
#[CoversClass(GitTrackedFiles::class)]
#[UsesClass(QueryResult::class)]
#[UsesClass(ScopeMatcher::class)]
#[UsesClass(SelectionFilter::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Entry::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(Path::class)]
#[UsesClass(PathPatternMatcher::class)]
#[UsesClass(Scope::class)]
#[UsesClass(Selection::class)]
#[Medium]
final class GitTrackedFilesTest extends TestCase
{
    public function testCollectIncludesDeletedIndexEntriesAndHonorsScope(): void
    {
        $root = sys_get_temp_dir() . '/guard-git-' . uniqid('', true);
        mkdir($root . '/package/build', 0777, true);
        try {
            (new Process(['git', '-C', $root, 'init', '--quiet']))->mustRun();
            file_put_contents($root . '/package/build/tracked file.txt', 'generated');
            file_put_contents($root . '/package/build/deleted.txt', 'generated');
            file_put_contents($root . '/package/build/keep.txt', 'intentional');
            (new Process(['git', '-C', $root, 'add', '--', 'package']))->mustRun();
            unlink($root . '/package/build/deleted.txt');
            file_put_contents($root . '/package/build/local.txt', 'untracked');
            $result = new QueryResult();
            (new GitTrackedFiles())->collect($root . '/package', new Selection('git-tracked', ['build/**'], ['build/keep.txt']), $result, new ScopeMatcher($root . '/package', $root . '/package', new Scope(['build'])));
            self::assertSame(['build/deleted.txt', 'build/tracked file.txt'], array_keys($result->files()));
            self::assertFileExists($root . '/package/build/local.txt');
            self::assertSame("package/build/deleted.txt\0package/build/keep.txt\0package/build/tracked file.txt\0", (new Process(['git', '-C', $root, 'ls-files', '-z']))->mustRun()->getOutput());
        } finally {
            (new Filesystem())->remove($root);
        }
    }

    public function testPathsPreservesNewlinesAndIgnoresFilesOutsideTheProject(): void
    {
        $root = sys_get_temp_dir() . '/guard-git-' . uniqid('', true);
        mkdir($root . '/package', 0777, true);
        try {
            (new Process(['git', '-C', $root, 'init', '--quiet']))->mustRun();
            file_put_contents($root . '/outside.txt', 'outside');
            file_put_contents($root . "/package/a\nb.txt", 'generated');
            (new Process(['git', '-C', $root, 'add', '--', '.']))->mustRun();
            self::assertSame(["a\nb.txt"], (new GitTrackedFiles())->paths($root . '/package'));
        } finally {
            (new Filesystem())->remove($root);
        }
    }

    public function testPathsReportsNonRepositoryAsConfigurationFailure(): void
    {
        $root = sys_get_temp_dir() . '/guard-not-git-' . uniqid('', true);
        mkdir($root);
        try {
            $this->expectException(PolicyException::class);
            $this->expectExceptionMessage('Cannot read Git index');
            (new GitTrackedFiles())->paths($root);
        } finally {
            rmdir($root);
        }
    }
}
