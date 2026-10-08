<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Model\Package;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Model\Package\ComposerManifest;
use Toolkit\DocGen\Model\Package\DiscoveredPackage;

/**
 * @covers \Toolkit\DocGen\Model\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Model\Package\ComposerManifest
 */
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
