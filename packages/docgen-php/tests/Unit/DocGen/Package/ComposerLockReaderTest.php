<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Package;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Package\ComposerLockReader;

/**
 * @covers \Toolkit\DocGen\Package\ComposerLockReader
 */
#[CoversClass(ComposerLockReader::class)]
final class ComposerLockReaderTest extends TestCase
{
    public function testReadSplitsRuntimeAndDevPackageNames(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-lock-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.lock', <<<'JSON'
{
    "packages": [{"name": "acme/lib"}, {"name": "acme/core"}],
    "packages-dev": [{"name": "phpunit/phpunit"}]
}
JSON);

        $lock = (new ComposerLockReader())->read($dir . '/composer.lock');

        self::assertNotNull($lock);
        self::assertSame(['acme/lib', 'acme/core'], $lock['runtime']);
        self::assertSame(['phpunit/phpunit'], $lock['dev']);
    }

    public function testReadReturnsEmptyListsForLockWithoutPackageSections(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-lock-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.lock', '{"content-hash": "abc"}');

        $lock = (new ComposerLockReader())->read($dir . '/composer.lock');

        self::assertNotNull($lock);
        self::assertSame([], $lock['runtime']);
        self::assertSame([], $lock['dev']);
    }

    public function testReadReturnsNullForMissingLockFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-lock-' . uniqid('', true);
        mkdir($dir, 0777, true);

        self::assertNull((new ComposerLockReader())->read($dir . '/composer.lock'));
    }

    public function testReadReturnsNullForMalformedLockFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-lock-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.lock', '{invalid');
        file_put_contents($dir . '/scalar.lock', '"just a string"');

        self::assertNull((new ComposerLockReader())->read($dir . '/composer.lock'));
        self::assertNull((new ComposerLockReader())->read($dir . '/scalar.lock'));
    }

    public function testNamesKeepsOnlyNamedEntries(): void
    {
        $names = (new ComposerLockReader())->names([
            ['name' => 'acme/lib'],
            ['name' => ''],
            ['version' => '1.0.0'],
            ['name' => 7],
            'acme/plain',
        ]);

        self::assertSame(['acme/lib'], $names);
    }

    public function testNamesReturnsEmptyListForNonArraySection(): void
    {
        $reader = new ComposerLockReader();

        self::assertSame([], $reader->names(null));
        self::assertSame([], $reader->names('acme/lib'));
    }
}
