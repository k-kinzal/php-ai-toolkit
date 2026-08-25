<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Config\ReportConfig;
use Toolkit\TreeGuard\Config\TreeGuardConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;
use Toolkit\TreeGuard\Filesystem\DirectoryListingReader;
use Toolkit\TreeGuard\Filesystem\DirectoryTreeScanner;
use Toolkit\TreeGuard\Filesystem\PathInclusionPolicy;
use Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver;
use Toolkit\TreeGuard\TreeGuardException;

/**
 * @covers \Toolkit\TreeGuard\Filesystem\DirectoryTreeScanner
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListingReader
 * @uses \Toolkit\TreeGuard\Filesystem\PathInclusionPolicy
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \Toolkit\TreeGuard\TreeGuardException
 * @uses \Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver
 */
#[CoversClass(DirectoryTreeScanner::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(DirectoryListingReader::class)]
#[UsesClass(PathInclusionPolicy::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(TreeGuardConfig::class)]
#[UsesClass(TreeGuardException::class)]
#[UsesClass(TreeGuardPathResolver::class)]
final class DirectoryTreeScannerTest extends TestCase
{
    public function testScanReturnsListingsForEveryDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-scan-' . uniqid('', true);
        mkdir($dir . '/src/A/B', 0777, true);
        touch($dir . '/src/Root.php');
        touch($dir . '/src/A/One.php');
        touch($dir . '/src/A/B/Two.php');
        $config = new TreeGuardConfig($dir, ['src'], [], [], new ReportConfig('ai', ['path', 'rule']));

        $listings = (new DirectoryTreeScanner())->scan($config);

        self::assertSame(['src', 'src/A', 'src/A/B'], array_keys($listings));
        self::assertSame(['Root.php'], $listings['src']->fileNames);
        self::assertSame(['A'], $listings['src']->dirNames);
        self::assertSame(['One.php'], $listings['src/A']->fileNames);
        self::assertSame(['B'], $listings['src/A']->dirNames);
        self::assertSame(['Two.php'], $listings['src/A/B']->fileNames);
        self::assertSame([], $listings['src/A/B']->dirNames);
    }

    public function testScanPrunesExcludedFilesAndSubtrees(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-scan-' . uniqid('', true);
        mkdir($dir . '/src/Keep', 0777, true);
        mkdir($dir . '/src/Skip/Nested', 0777, true);
        touch($dir . '/src/Keep/Kept.php');
        touch($dir . '/src/Keep/dropped.tmp');
        touch($dir . '/src/Skip/Nested/Hidden.php');
        $config = new TreeGuardConfig($dir, ['src'], ['src/Skip', '*.tmp'], [], new ReportConfig('ai', ['path', 'rule']));

        $listings = (new DirectoryTreeScanner())->scan($config);

        self::assertSame(['src', 'src/Keep'], array_keys($listings));
        self::assertSame(['Keep'], $listings['src']->dirNames);
        self::assertSame(['Kept.php'], $listings['src/Keep']->fileNames);
    }

    public function testScanMergesMultiplePaths(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-scan-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        mkdir($dir . '/skills', 0777, true);
        touch($dir . '/src/App.php');
        touch($dir . '/skills/SKILL.md');
        $config = new TreeGuardConfig($dir, ['src', 'skills'], [], [], new ReportConfig('ai', ['path', 'rule']));

        $listings = (new DirectoryTreeScanner())->scan($config);

        self::assertSame(['skills', 'src'], array_keys($listings));
        self::assertSame(['SKILL.md'], $listings['skills']->fileNames);
        self::assertSame(['App.php'], $listings['src']->fileNames);
    }

    public function testScanReturnsRootRelativePathsWhenScanningProjectRoot(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-scan-' . uniqid('', true);
        mkdir($dir . '/.github/workflows', 0777, true);
        mkdir($dir . '/scripts', 0777, true);
        mkdir($dir . '/vendor/package', 0777, true);
        touch($dir . '/composer.json');
        touch($dir . '/.github/workflows/ci.yml');
        touch($dir . '/scripts/build.sh');
        touch($dir . '/vendor/package/Dropped.php');
        $config = new TreeGuardConfig($dir, ['.'], ['vendor'], [], new ReportConfig('ai', ['path', 'rule']));

        $listings = (new DirectoryTreeScanner())->scan($config);

        self::assertSame(['.', '.github', '.github/workflows', 'scripts'], array_keys($listings));
        self::assertSame(['composer.json'], $listings['.']->fileNames);
        self::assertSame(['.github', 'scripts'], $listings['.']->dirNames);
        self::assertSame(['build.sh'], $listings['scripts']->fileNames);
    }

    public function testScanRejectsMissingPath(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-scan-' . uniqid('', true);
        mkdir($dir);
        $config = new TreeGuardConfig($dir, ['src'], [], [], new ReportConfig('ai', ['path', 'rule']));

        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Configured path is not a directory: src');

        (new DirectoryTreeScanner())->scan($config);
    }

    public function testScanRejectsFilePath(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-scan-' . uniqid('', true);
        mkdir($dir);
        touch($dir . '/src');
        $config = new TreeGuardConfig($dir, ['src'], [], [], new ReportConfig('ai', ['path', 'rule']));

        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Configured path is not a directory: src');

        (new DirectoryTreeScanner())->scan($config);
    }
}
