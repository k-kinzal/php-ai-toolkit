<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\LimitConfigReader;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Config\LimitConfigReader
 * @uses \Toolkit\LocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\LocGuard\Config\ConfigScalarReader
 */
#[CoversClass(LimitConfigReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
final class LimitConfigReaderTest extends TestCase
{
    public function testReadReturnsPartialNestedLimits(): void
    {
        $limits = (new LimitConfigReader())->read([
            'file' => ['lines' => 10],
            'method' => ['cyclomatic_complexity' => null],
        ]);

        self::assertSame([
            'file.lines' => 10,
            'method.cyclomatic_complexity' => null,
        ], $limits);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(LocGuardException::class);

        (new LimitConfigReader())->read('strict');
    }

    public function testReadRejectsUnknownMetric(): void
    {
        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('unsupported key "branches"');

        (new LimitConfigReader())->read(['method' => ['branches' => 10]]);
    }
}
