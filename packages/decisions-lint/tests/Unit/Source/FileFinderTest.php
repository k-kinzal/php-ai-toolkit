<?php

declare(strict_types=1);

namespace Tests\Unit\Source;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Source\FileFinder;
use DecisionsLint\Source\GlobMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Source\FileFinder
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Source\GlobMatcher
 */
#[CoversClass(FileFinder::class)]
#[UsesClass(LintException::class)]
#[UsesClass(GlobMatcher::class)]
final class FileFinderTest extends TestCase
{
    public function testFindListsFilesUnderPathsMinusExcludes(): void
    {
        $root = sys_get_temp_dir() . '/decisions-finder-' . uniqid();
        mkdir($root . '/src/Generated', 0777, true);
        mkdir($root . '/tests');
        file_put_contents($root . '/src/A.php', '<?php');
        file_put_contents($root . '/src/Generated/B.php', '<?php');
        file_put_contents($root . '/src/notes.md', '# notes');
        file_put_contents($root . '/tests/ATest.php', '<?php');
        file_put_contents($root . '/README.md', '# readme');
        try {
            $finder = new FileFinder($root);
            self::assertSame(['src/A.php', 'src/notes.md'], $finder->find(['src'], ['src/Generated/**']));
            self::assertSame(['README.md', 'src/A.php', 'src/Generated/B.php'], $finder->find(['README.md', 'src/**/*.php'], []));
            self::assertSame(['tests/ATest.php'], $finder->find(['tests', 'tests'], []));
        } finally {
            unlink($root . '/src/A.php');
            unlink($root . '/src/Generated/B.php');
            unlink($root . '/src/notes.md');
            unlink($root . '/tests/ATest.php');
            unlink($root . '/README.md');
            rmdir($root . '/src/Generated');
            rmdir($root . '/src');
            rmdir($root . '/tests');
            rmdir($root);
        }
    }

    public function testFindRejectsAPathThatDoesNotExist(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Path "missing" does not exist under');
        (new FileFinder(sys_get_temp_dir(), new GlobMatcher()))->find(['missing'], []);
    }

    public function testWalkReturnsRelativePathsOfRegularFiles(): void
    {
        $root = sys_get_temp_dir() . '/decisions-walk-' . uniqid();
        mkdir($root . '/a/b', 0777, true);
        file_put_contents($root . '/a/b/c.txt', 'c');
        try {
            self::assertSame(['a/b/c.txt'], (new FileFinder($root))->walk($root . '/a'));
        } finally {
            unlink($root . '/a/b/c.txt');
            rmdir($root . '/a/b');
            rmdir($root . '/a');
            rmdir($root);
        }
    }
}
