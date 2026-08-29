<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigStringListReader;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Config\ConfigStringListReader
 */
#[CoversClass(ConfigStringListReader::class)]
final class ConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsConfiguredStringList(): void
    {
        self::assertSame(['src'], (new ConfigStringListReader())->read(['paths' => ['src']], 'paths', []));
    }

    public function testReadRejectsInvalidStringList(): void
    {
        $this->expectException(LocGuardException::class);

        (new ConfigStringListReader())->read(['paths' => [1]], 'paths', []);
    }

    public function testReadRequiredRejectsEmptyList(): void
    {
        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('must contain at least one entry');

        (new ConfigStringListReader())->readRequired(['roots' => []], 'roots', 'scan', false);
    }
}
