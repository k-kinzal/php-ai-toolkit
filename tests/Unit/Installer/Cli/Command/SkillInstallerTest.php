<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli\Command;

use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_link;
use function mkdir;

use PhpAiToolkit\Installer\Cli\Command\SkillFilesystemOperator;
use PhpAiToolkit\Installer\Cli\Command\SkillInstallationWriter;
use PhpAiToolkit\Installer\Cli\Command\SkillInstaller;
use PhpAiToolkit\Installer\RelativePathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(SkillInstaller::class)]
#[UsesClass(RelativePathResolver::class)]
#[UsesClass(SkillFilesystemOperator::class)]
#[UsesClass(SkillInstallationWriter::class)]
final class SkillInstallerTest extends TestCase
{
    public function testInstallCreatesSymlink(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        mkdir($path . '/source/test-skill', 0755, true);
        mkdir($path . '/target', 0755, true);
        file_put_contents($path . '/source/test-skill/SKILL.md', 'content');
        $output = [];

        try {
            $result = (new SkillInstaller(
                new SkillFilesystemOperator(),
                new SkillInstallationWriter(static function (string $message) use (&$output): void {
                    $output[] = $message;
                }),
            ))->install($path . '/source', $path . '/target', 'test-skill', false, false);

            self::assertSame('installed', $result);
            self::assertTrue(is_link($path . '/target/test-skill'));
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }

    public function testInstallCopiesSkillWhenCopyModeEnabled(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        mkdir($path . '/source/test-skill', 0755, true);
        mkdir($path . '/target', 0755, true);
        file_put_contents($path . '/source/test-skill/SKILL.md', 'content');
        $output = [];

        try {
            $result = (new SkillInstaller(
                new SkillFilesystemOperator(),
                new SkillInstallationWriter(static function (string $message) use (&$output): void {
                    $output[] = $message;
                }),
            ))->install($path . '/source', $path . '/target', 'test-skill', false, true);

            self::assertSame('installed', $result);
            self::assertTrue(is_dir($path . '/target/test-skill'));
            self::assertSame('content', file_get_contents($path . '/target/test-skill/SKILL.md'));
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }

    public function testInstallSkipsExistingSkillWithoutForce(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        mkdir($path . '/source/test-skill', 0755, true);
        mkdir($path . '/target/test-skill', 0755, true);
        $output = [];

        try {
            $result = (new SkillInstaller(
                new SkillFilesystemOperator(),
                new SkillInstallationWriter(static function (string $message) use (&$output): void {
                    $output[] = $message;
                }),
            ))->install($path . '/source', $path . '/target', 'test-skill', false, false);

            self::assertSame('skipped', $result);
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }

    public function testInstallOverwritesExistingWithForce(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        mkdir($path . '/source/test-skill', 0755, true);
        mkdir($path . '/target/test-skill', 0755, true);
        file_put_contents($path . '/source/test-skill/SKILL.md', 'content');
        file_put_contents($path . '/target/test-skill/old.txt', 'old');
        $output = [];

        try {
            $result = (new SkillInstaller(
                new SkillFilesystemOperator(),
                new SkillInstallationWriter(static function (string $message) use (&$output): void {
                    $output[] = $message;
                }),
            ))->install($path . '/source', $path . '/target', 'test-skill', true, false);

            self::assertSame('installed', $result);
            self::assertTrue(is_link($path . '/target/test-skill'));
            self::assertFileDoesNotExist($path . '/target/test-skill/old.txt');
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }
}
