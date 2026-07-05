<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli\Command;

use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_link;
use function mkdir;

use PhpAiToolkit\Installer\Cli\Command\SkillFilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(SkillFilesystemOperator::class)]
final class SkillFilesystemOperatorTest extends TestCase
{
    public function testEnsureDirectoryCreatesNestedDirectory(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $target = $path . '/nested/skills';

        try {
            (new SkillFilesystemOperator())->ensureDirectory($target);

            self::assertTrue(is_dir($target));
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }

    public function testRemoveDeletesDirectoryTree(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        mkdir($path . '/nested', 0755, true);
        file_put_contents($path . '/nested/file.txt', 'content');

        (new SkillFilesystemOperator())->remove($path);

        self::assertDirectoryDoesNotExist($path);
    }

    public function testCopyDirectoryCopiesNestedFiles(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $source = $path . '/source';
        $target = $path . '/target';
        mkdir($source . '/nested', 0755, true);
        file_put_contents($source . '/nested/file.txt', 'content');

        try {
            self::assertTrue((new SkillFilesystemOperator())->copyDirectory($source, $target));
            self::assertSame('content', file_get_contents($target . '/nested/file.txt'));
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }

    public function testSymlinkCreatesSymlink(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        mkdir($path . '/source', 0755, true);

        try {
            self::assertTrue((new SkillFilesystemOperator())->symlink($path . '/source', $path . '/link'));
            self::assertTrue(is_link($path . '/link'));
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }
}
