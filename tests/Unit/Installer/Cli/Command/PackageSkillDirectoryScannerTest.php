<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli\Command;

use function file_put_contents;
use function mkdir;

use PhpAiToolkit\Installer\Cli\Command\PackageSkillDirectoryScanner;
use PhpAiToolkit\Installer\Cli\Command\SkillFilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(PackageSkillDirectoryScanner::class)]
#[UsesClass(SkillFilesystemOperator::class)]
final class PackageSkillDirectoryScannerTest extends TestCase
{
    public function testScanReturnsSkillDirectoriesAndIgnoresGitkeep(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        mkdir($path . '/skill-a', 0755, true);
        mkdir($path . '/skill-b', 0755, true);
        file_put_contents($path . '/.gitkeep', '');
        file_put_contents($path . '/README.md', '');

        try {
            self::assertSame(['skill-a', 'skill-b'], (new PackageSkillDirectoryScanner())->scan($path));
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }
}
