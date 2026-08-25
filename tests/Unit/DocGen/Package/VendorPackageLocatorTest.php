<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Package;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Package\VendorPackageLocator;

/**
 * @covers \Toolkit\DocGen\Package\VendorPackageLocator
 */
#[CoversClass(VendorPackageLocator::class)]
final class VendorPackageLocatorTest extends TestCase
{
    public function testLocateFindsMatchingManifestsAcrossSearchDirectories(): void
    {
        $base = sys_get_temp_dir() . '/docgen-vendor-' . uniqid('', true);
        mkdir($base . '/first/vendor/acme/one', 0777, true);
        mkdir($base . '/first/vendor/other/two', 0777, true);
        mkdir($base . '/second/vendor/acme/zed', 0777, true);
        file_put_contents($base . '/first/vendor/acme/one/composer.json', '{"name": "acme/one"}');
        file_put_contents($base . '/first/vendor/other/two/composer.json', '{"name": "other/two"}');
        file_put_contents($base . '/second/vendor/acme/zed/composer.json', '{"name": "acme/zed"}');

        $paths = (new VendorPackageLocator())->locate([$base . '/first', $base . '/second'], ['acme/*']);

        self::assertSame(
            [
                $base . '/first/vendor/acme/one/composer.json',
                $base . '/second/vendor/acme/zed/composer.json',
            ],
            $paths,
        );
    }

    public function testLocateReturnsEmptyListForEmptyGlobs(): void
    {
        $base = sys_get_temp_dir() . '/docgen-vendor-' . uniqid('', true);
        mkdir($base . '/vendor/acme/one', 0777, true);
        file_put_contents($base . '/vendor/acme/one/composer.json', '{"name": "acme/one"}');

        self::assertSame([], (new VendorPackageLocator())->locate([$base], []));
    }

    public function testLocateReturnsEmptyListWhenNoNameMatchesGlob(): void
    {
        $base = sys_get_temp_dir() . '/docgen-vendor-' . uniqid('', true);
        mkdir($base . '/with/vendor/acme/one', 0777, true);
        mkdir($base . '/plain', 0777, true);
        file_put_contents($base . '/with/vendor/acme/one/composer.json', '{"name": "acme/one"}');

        self::assertSame([], (new VendorPackageLocator())->locate([$base . '/with', $base . '/plain'], ['zzz/*']));
    }
}
