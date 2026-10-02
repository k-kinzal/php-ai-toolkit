<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Git;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Git\TempDirectory;

/**
 * @covers \Toolkit\DocGen\Git\TempDirectory
 * @uses \Toolkit\DocGen\DocGenException
 */
#[CoversClass(TempDirectory::class)]
#[UsesClass(DocGenException::class)]
final class TempDirectoryTest extends TestCase
{
    public function testCreateMakesAnEmptyDirectoryBelowTheSystemTempDirectory(): void
    {
        $temp = new TempDirectory();
        $path = $temp->create('docgen-test-');

        self::assertDirectoryExists($path);
        self::assertStringStartsWith(rtrim(sys_get_temp_dir(), '/') . '/docgen-test-', $path);
        self::assertSame(['.', '..'], scandir($path));

        $temp->remove($path);
    }

    public function testCreateMakesADifferentDirectoryOnEveryCall(): void
    {
        $temp = new TempDirectory();
        $first = $temp->create('docgen-test-');
        $second = $temp->create('docgen-test-');

        self::assertNotSame($first, $second);

        $temp->remove($first);
        $temp->remove($second);
    }

    #[WithoutErrorHandler]
    public function testCreateReportsADirectoryItCannotMake(): void
    {
        file_put_contents(rtrim(sys_get_temp_dir(), '/') . '/docgen-not-a-directory', 'blocked');

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Failed to create the temporary directory');

        (new TempDirectory())->create('docgen-not-a-directory/child-');
    }

    public function testRemoveDeletesADirectoryTreeWithItsContents(): void
    {
        $temp = new TempDirectory();
        $path = $temp->create('docgen-test-');
        mkdir($path . '/nested/deeper', 0700, true);
        file_put_contents($path . '/nested/deeper/file.txt', 'content');

        $temp->remove($path);

        self::assertDirectoryDoesNotExist($path);
    }

    public function testRemoveUnlinksALinkWithoutFollowingIt(): void
    {
        $temp = new TempDirectory();
        $path = $temp->create('docgen-test-');
        $target = $temp->create('docgen-target-');
        file_put_contents($target . '/keep.txt', 'content');
        symlink($target, $path . '/vendor');

        $temp->remove($path);

        self::assertDirectoryDoesNotExist($path);
        self::assertFileExists($target . '/keep.txt');

        $temp->remove($target);
    }

    public function testRemoveAcceptsAPathThatIsNotThere(): void
    {
        $temp = new TempDirectory();

        $temp->remove(rtrim(sys_get_temp_dir(), '/') . '/docgen-never-created');

        self::assertDirectoryDoesNotExist(rtrim(sys_get_temp_dir(), '/') . '/docgen-never-created');
    }
}
