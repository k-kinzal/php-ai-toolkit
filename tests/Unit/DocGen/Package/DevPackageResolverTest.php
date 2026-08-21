<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Package;

use PhpAiToolkit\DocGen\Config\RepositoryUrl;
use PhpAiToolkit\DocGen\Package\ComposerLockReader;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\ComposerManifestReader;
use PhpAiToolkit\DocGen\Package\DevPackageResolver;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\VendorPackageLocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DevPackageResolver::class)]
#[UsesClass(ComposerLockReader::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ComposerManifestReader::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(RepositoryUrl::class)]
#[UsesClass(VendorPackageLocator::class)]
final class DevPackageResolverTest extends TestCase
{
    public function testDevNamesPrefersTheLockFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-devnames-' . uniqid('', true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        mkdir($dir . '/vendor/phpunit/phpunit', 0777, true);
        file_put_contents($dir . '/vendor/acme/lib/composer.json', '{"name": "acme/lib"}');
        file_put_contents($dir . '/vendor/phpunit/phpunit/composer.json', '{"name": "phpunit/phpunit"}');
        file_put_contents($dir . '/composer.lock', <<<'JSON'
{
    "packages": [{"name": "acme/lib"}],
    "packages-dev": [{"name": "phpunit/phpunit"}]
}
JSON);
        $root = new DiscoveredPackage(new ComposerManifest($dir, 'acme/root', '', [], [], [], [], []), false);

        $devNames = (new DevPackageResolver())->devNames([$dir], [$root]);

        self::assertSame(['phpunit/phpunit' => true], $devNames);
    }

    public function testDevNamesFallsBackToRequireClosureWithoutLockFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-devnames-' . uniqid('', true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        mkdir($dir . '/vendor/acme/core', 0777, true);
        mkdir($dir . '/vendor/phpunit/phpunit', 0777, true);
        file_put_contents($dir . '/vendor/acme/lib/composer.json', '{"name": "acme/lib", "require": {"acme/core": "^1.0"}}');
        file_put_contents($dir . '/vendor/acme/core/composer.json', '{"name": "acme/core"}');
        file_put_contents($dir . '/vendor/phpunit/phpunit/composer.json', '{"name": "phpunit/phpunit"}');
        $manifest = new ComposerManifest($dir, 'acme/root', '', [], [], ['acme/lib' => '^1.0'], ['phpunit/phpunit' => '^11.0'], []);

        $devNames = (new DevPackageResolver())->devNames([$dir], [new DiscoveredPackage($manifest, false)]);

        self::assertSame(['phpunit/phpunit' => true], $devNames);
    }

    public function testLockDevNamesKeepsPackagesRequiredAtRuntimeByAnotherLock(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-devnames-' . uniqid('', true);
        mkdir($dir . '/packages/app', 0777, true);
        file_put_contents($dir . '/composer.lock', '{"packages": [], "packages-dev": [{"name": "acme/lib"}]}');
        file_put_contents($dir . '/packages/app/composer.lock', '{"packages": [{"name": "acme/lib"}], "packages-dev": [{"name": "acme/tool"}]}');

        $devNames = (new DevPackageResolver())->lockDevNames([$dir, $dir . '/packages/app']);

        self::assertSame(['acme/tool' => true], $devNames);
    }

    public function testLockDevNamesReturnsNullWithoutAnyLockFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-devnames-' . uniqid('', true);
        mkdir($dir, 0777, true);

        self::assertNull((new DevPackageResolver())->lockDevNames([$dir]));
    }

    public function testRequireClosureDevNamesFollowsTransitiveRequires(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-devnames-' . uniqid('', true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        mkdir($dir . '/vendor/acme/deep', 0777, true);
        mkdir($dir . '/vendor/acme/tool', 0777, true);
        file_put_contents($dir . '/vendor/acme/lib/composer.json', '{"name": "acme/lib", "require": {"acme/deep": "^1.0"}}');
        file_put_contents($dir . '/vendor/acme/deep/composer.json', '{"name": "acme/deep", "require": {"acme/lib": "^1.0"}}');
        file_put_contents($dir . '/vendor/acme/tool/composer.json', '{"name": "acme/tool", "require": {"acme/deep": "^1.0"}}');
        $manifest = new ComposerManifest($dir, 'acme/root', '', [], [], ['acme/lib' => '^1.0'], ['acme/tool' => '^1.0'], []);

        $devNames = (new DevPackageResolver())->requireClosureDevNames([$dir], [new DiscoveredPackage($manifest, false)]);

        self::assertSame(['acme/tool' => true], $devNames);
    }

    public function testRequireClosureDevNamesReturnsEmptyMapWithoutInstalledPackages(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-devnames-' . uniqid('', true);
        mkdir($dir, 0777, true);
        $manifest = new ComposerManifest($dir, 'acme/root', '', [], [], ['acme/lib' => '^1.0'], [], []);

        self::assertSame([], (new DevPackageResolver())->requireClosureDevNames([$dir], [new DiscoveredPackage($manifest, false)]));
    }

    public function testInstalledRequiresMapsEveryInstalledPackage(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-devnames-' . uniqid('', true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        mkdir($dir . '/vendor/acme/core', 0777, true);
        file_put_contents($dir . '/vendor/acme/lib/composer.json', '{"name": "acme/lib", "require": {"acme/core": "^1.0", "php": ">=8.0"}}');
        file_put_contents($dir . '/vendor/acme/core/composer.json', '{"name": "acme/core"}');

        $requires = (new DevPackageResolver())->installedRequires([$dir]);

        self::assertSame(['acme/core' => [], 'acme/lib' => ['acme/core', 'php']], $requires);
    }
}
