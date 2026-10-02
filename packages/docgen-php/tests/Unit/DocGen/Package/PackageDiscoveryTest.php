<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Package;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Config\DocGenConfig;
use Toolkit\DocGen\Config\RepositoryUrl;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Package\ComposerLockReader;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\ComposerManifestReader;
use Toolkit\DocGen\Package\DevPackageResolver;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageDiscovery;
use Toolkit\DocGen\Package\VendorPackageLocator;

/**
 * @covers \Toolkit\DocGen\Package\PackageDiscovery
 * @uses \Toolkit\DocGen\Package\ComposerLockReader
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Package\DevPackageResolver
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Config\DocGenConfig
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\Package\VendorPackageLocator
 */
#[CoversClass(PackageDiscovery::class)]
#[UsesClass(ComposerLockReader::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ComposerManifestReader::class)]
#[UsesClass(DevPackageResolver::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(RepositoryUrl::class)]
#[UsesClass(VendorPackageLocator::class)]
final class PackageDiscoveryTest extends TestCase
{
    public function testDiscoverFindsRootPackageWithDotPattern(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.json', '{"name": "acme/root"}');
        $config = new DocGenConfig($dir, ['.'], [], [], $dir . '/docs', null, null, null);

        $packages = (new PackageDiscovery())->discover($config);

        self::assertCount(1, $packages);
        self::assertSame('acme/root', $packages[0]->manifest->name);
        self::assertSame(realpath($dir), $packages[0]->manifest->directory);
        self::assertFalse($packages[0]->isVendor);
    }

    public function testDiscoverExpandsGlobAndSortsPackagesByName(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir . '/packages/a', 0777, true);
        mkdir($dir . '/packages/b', 0777, true);
        mkdir($dir . '/packages/no-manifest', 0777, true);
        file_put_contents($dir . '/packages/a/composer.json', '{"name": "acme/zeta"}');
        file_put_contents($dir . '/packages/b/composer.json', '{"name": "acme/alpha"}');
        $config = new DocGenConfig($dir, ['packages/*'], [], [], $dir . '/docs', null, null, null);

        $packages = (new PackageDiscovery())->discover($config);

        self::assertCount(2, $packages);
        self::assertSame('acme/alpha', $packages[0]->manifest->name);
        self::assertSame('acme/zeta', $packages[1]->manifest->name);
    }

    public function testDiscoverDeduplicatesDirectoriesAndPackageNames(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir . '/packages/a', 0777, true);
        mkdir($dir . '/packages/b', 0777, true);
        file_put_contents($dir . '/composer.json', '{"name": "acme/root"}');
        file_put_contents($dir . '/packages/a/composer.json', '{"name": "acme/a"}');
        file_put_contents($dir . '/packages/b/composer.json', '{"name": "acme/a"}');
        $config = new DocGenConfig($dir, ['.', 'packages/*', 'packages/a'], [], [], $dir . '/docs', null, null, null);

        $packages = (new PackageDiscovery())->discover($config);

        self::assertCount(2, $packages);
        self::assertSame('acme/a', $packages[0]->manifest->name);
        self::assertSame(realpath($dir . '/packages/a'), $packages[0]->manifest->directory);
        self::assertSame('acme/root', $packages[1]->manifest->name);
    }

    public function testDiscoverThrowsWhenNoManifestIsFound(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir . '/packages/empty', 0777, true);
        $config = new DocGenConfig($dir, ['packages/*'], [], [], $dir . '/docs', null, null, null);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('No composer packages found. Pass --packages=GLOBS with directory globs where at least one directory contains a composer.json.');

        (new PackageDiscovery())->discover($config);
    }

    public function testDiscoverAppendsMatchingRuntimeVendorPackagesOnly(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        mkdir($dir . '/vendor/acme/tool', 0777, true);
        mkdir($dir . '/vendor/other/pkg', 0777, true);
        file_put_contents($dir . '/composer.json', '{"name": "acme/root"}');
        file_put_contents($dir . '/composer.lock', '{"packages": [{"name": "acme/lib"}, {"name": "other/pkg"}], "packages-dev": [{"name": "acme/tool"}]}');
        file_put_contents($dir . '/vendor/acme/lib/composer.json', '{"name": "acme/lib"}');
        file_put_contents($dir . '/vendor/acme/tool/composer.json', '{"name": "acme/tool"}');
        file_put_contents($dir . '/vendor/other/pkg/composer.json', '{"name": "other/pkg"}');
        $config = new DocGenConfig($dir, ['.'], ['acme/*'], [], $dir . '/docs', null, null, null);

        $packages = (new PackageDiscovery())->discover($config);

        self::assertCount(2, $packages);
        self::assertSame('acme/root', $packages[0]->manifest->name);
        self::assertFalse($packages[0]->isVendor);
        self::assertSame('acme/lib', $packages[1]->manifest->name);
        self::assertTrue($packages[1]->isVendor);
        self::assertFalse($packages[1]->isDevDependency);
    }

    public function testDiscoverAppendsMatchingDevVendorPackagesForVendorDevGlobs(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        mkdir($dir . '/vendor/acme/tool', 0777, true);
        file_put_contents($dir . '/composer.json', '{"name": "acme/root"}');
        file_put_contents($dir . '/composer.lock', '{"packages": [{"name": "acme/lib"}], "packages-dev": [{"name": "acme/tool"}]}');
        file_put_contents($dir . '/vendor/acme/lib/composer.json', '{"name": "acme/lib"}');
        file_put_contents($dir . '/vendor/acme/tool/composer.json', '{"name": "acme/tool"}');
        $config = new DocGenConfig($dir, ['.'], ['acme/lib'], [], $dir . '/docs', null, null, null, ['acme/*']);

        $packages = (new PackageDiscovery())->discover($config);

        self::assertCount(3, $packages);
        self::assertSame('acme/lib', $packages[1]->manifest->name);
        self::assertFalse($packages[1]->isDevDependency);
        self::assertSame('acme/tool', $packages[2]->manifest->name);
        self::assertTrue($packages[2]->isVendor);
        self::assertTrue($packages[2]->isDevDependency);
    }

    public function testWithVendorPackagesAppendsOnlyNewMatchingManifests(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        mkdir($dir . '/vendor/acme/root', 0777, true);
        file_put_contents($dir . '/composer.lock', '{"packages": [{"name": "acme/lib"}, {"name": "acme/root"}], "packages-dev": []}');
        file_put_contents($dir . '/vendor/acme/lib/composer.json', '{"name": "acme/lib"}');
        file_put_contents($dir . '/vendor/acme/root/composer.json', '{"name": "acme/root"}');
        $config = new DocGenConfig($dir, ['.'], ['acme/*'], [], $dir . '/docs', null, null, null);
        $existing = new DiscoveredPackage(new ComposerManifest($dir, 'acme/root', '', [], [], [], [], []), false);

        $packages = (new PackageDiscovery())->withVendorPackages($config, [$existing]);

        self::assertCount(2, $packages);
        self::assertSame($existing, $packages[0]);
        self::assertSame('acme/lib', $packages[1]->manifest->name);
        self::assertTrue($packages[1]->isVendor);
    }

    public function testWithVendorPackagesReturnsPackagesUnchangedForEmptyVendorConfig(): void
    {
        $config = new DocGenConfig('/nowhere', ['.'], [], [], '/nowhere/docs', null, null, null);
        $existing = new DiscoveredPackage(new ComposerManifest('/nowhere', 'acme/root', '', [], [], [], [], []), false);

        self::assertSame([$existing], (new PackageDiscovery())->withVendorPackages($config, [$existing]));
    }

    public function testAppendVendorPackagesKeepsOnlyTheRequestedDependencyKind(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        mkdir($dir . '/vendor/acme/tool', 0777, true);
        file_put_contents($dir . '/vendor/acme/lib/composer.json', '{"name": "acme/lib"}');
        file_put_contents($dir . '/vendor/acme/tool/composer.json', '{"name": "acme/tool"}');
        $paths = [$dir . '/vendor/acme/lib/composer.json', $dir . '/vendor/acme/tool/composer.json'];

        $packages = (new PackageDiscovery())->appendVendorPackages([], $paths, ['acme/tool' => true], true);

        self::assertCount(1, $packages);
        self::assertSame('acme/tool', $packages[0]->manifest->name);
        self::assertTrue($packages[0]->isDevDependency);
    }

    public function testAppendVendorPackagesSkipsAlreadyDiscoveredNames(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        file_put_contents($dir . '/vendor/acme/lib/composer.json', '{"name": "acme/lib"}');
        $existing = new DiscoveredPackage(new ComposerManifest($dir, 'acme/lib', '', [], [], [], [], []), false);

        $packages = (new PackageDiscovery())->appendVendorPackages([$existing], [$dir . '/vendor/acme/lib/composer.json'], [], false);

        self::assertSame([$existing], $packages);
    }

    public function testSearchDirectoriesListsRootAndEveryPackageDirectory(): void
    {
        $config = new DocGenConfig('/proj', ['.'], [], [], '/proj/docs', null, null, null);
        $package = new DiscoveredPackage(new ComposerManifest('/proj/packages/app', 'acme/app', '', [], [], [], [], []), false);

        self::assertSame(['/proj', '/proj/packages/app'], (new PackageDiscovery())->searchDirectories($config, [$package]));
    }

    public function testCandidateDirectoriesReturnsRootForDotPattern(): void
    {
        self::assertSame(['/any/root'], (new PackageDiscovery())->candidateDirectories('/any/root', '.'));
    }

    public function testCandidateDirectoriesExpandsGlobToExistingDirectories(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir . '/packages/a', 0777, true);
        mkdir($dir . '/packages/b', 0777, true);
        file_put_contents($dir . '/packages/note.txt', 'not a directory');

        $directories = (new PackageDiscovery())->candidateDirectories($dir, 'packages/*');

        self::assertSame([$dir . '/packages/a', $dir . '/packages/b'], $directories);
        self::assertSame([], (new PackageDiscovery())->candidateDirectories($dir, 'missing/*'));
    }
}
