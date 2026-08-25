<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LimitConfigReader;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Config\LimitConfigReader
 * @uses \Toolkit\LocGuard\Config\ConfigScalarReader
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 */
#[CoversClass(LimitConfigReader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(LimitConfig::class)]
final class LimitConfigReaderTest extends TestCase
{
    public function testReadReturnsLimitConfig(): void
    {
        $limits = (new LimitConfigReader())->read(['max_file_lines' => 10]);

        self::assertSame(10, $limits->maxFileLines);
        self::assertSame(350, $limits->maxFileNcloc);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(LocGuardException::class);

        (new LimitConfigReader())->read('strict');
    }
}
