<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Package;

use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiscoveredPackage::class)]
#[UsesClass(ComposerManifest::class)]
final class DiscoveredPackageTest extends TestCase
{
    public function testStoresManifestAndVendorFlag(): void
    {
        $manifest = new ComposerManifest('/projects/lib', 'acme/lib', '', [], [], [], [], []);

        $package = new DiscoveredPackage($manifest, true);

        self::assertSame($manifest, $package->manifest);
        self::assertTrue($package->isVendor);
        self::assertFalse($package->isDevDependency);
    }

    public function testStoresProjectPackageAsNonVendor(): void
    {
        $package = new DiscoveredPackage(new ComposerManifest('/projects/app', 'acme/app', '', [], [], [], [], []), false);

        self::assertSame('acme/app', $package->manifest->name);
        self::assertFalse($package->isVendor);
        self::assertFalse($package->isDevDependency);
    }

    public function testStoresDevDependencyFlagForDevVendorPackage(): void
    {
        $package = new DiscoveredPackage(new ComposerManifest('/projects/app/vendor/phpunit/phpunit', 'phpunit/phpunit', '', [], [], [], [], []), true, true);

        self::assertTrue($package->isVendor);
        self::assertTrue($package->isDevDependency);
    }
}
