<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Tree\Filesystem;

use Guard\Collect\Tree\Filesystem\DirectoryListing;
use Guard\Collect\Tree\Filesystem\DirectoryListingReader;
use Guard\Collect\Tree\Filesystem\DirectoryTreeScanner;
use Guard\Collect\Tree\Filesystem\PathInclusionPolicy;
use Guard\Collect\Tree\Filesystem\PathResolver;
use Guard\Config\Tree\StructureConfig;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Tree\Filesystem\DirectoryTreeScanner
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListing
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListingReader
 * @uses \Guard\Collect\Tree\Filesystem\PathInclusionPolicy
 * @uses \Guard\Collect\Tree\Filesystem\PathResolver
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\StructureConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(DirectoryTreeScanner::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(DirectoryListingReader::class)]
#[UsesClass(PathInclusionPolicy::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfig::class)]
#[UsesClass(StructureConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class DirectoryTreeScannerTest extends TestCase
{
    public function testScanReturnsListingsForEveryDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-scan-' . uniqid('', true);
        mkdir($dir . '/src/A/B', 0777, true);
        touch($dir . '/src/Root.php');
        touch($dir . '/src/A/One.php');
        touch($dir . '/src/A/B/Two.php');
        $config = new StructureConfig($dir, ['src'], [], []);

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
        $config = new StructureConfig($dir, ['src'], ['src/Skip', '*.tmp'], []);

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
        $config = new StructureConfig($dir, ['src', 'skills'], [], []);

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
        $config = new StructureConfig($dir, ['.'], ['vendor'], []);

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
        $config = new StructureConfig($dir, ['src'], [], []);

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Configured path is not a directory: src');

        (new DirectoryTreeScanner())->scan($config);
    }

    public function testScanRejectsFilePath(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-scan-' . uniqid('', true);
        mkdir($dir);
        touch($dir . '/src');
        $config = new StructureConfig($dir, ['src'], [], []);

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Configured path is not a directory: src');

        (new DirectoryTreeScanner())->scan($config);
    }
}
