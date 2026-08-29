<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Config\ConfigScalarReader
 */
#[CoversClass(ConfigScalarReader::class)]
final class ConfigScalarReaderTest extends TestCase
{
    public function testStringReturnsNonEmptyString(): void
    {
        self::assertSame('ai', (new ConfigScalarReader())->string(['reporter' => 'ai'], 'reporter', 'text'));
    }

    public function testNullablePositiveIntReturnsPositiveIntegerAndNull(): void
    {
        $reader = new ConfigScalarReader();

        self::assertSame(10, $reader->nullablePositiveInt(['lines' => 10], 'lines', 'limits.file'));
        self::assertNull($reader->nullablePositiveInt(['lines' => null], 'lines', 'limits.file'));
    }

    public function testStringRejectsEmptyString(): void
    {
        $this->expectException(LocGuardException::class);

        (new ConfigScalarReader())->string(['reporter' => ''], 'reporter', 'ai');
    }

    public function testNullablePositiveIntRejectsNonPositiveInteger(): void
    {
        $this->expectException(LocGuardException::class);

        (new ConfigScalarReader())->nullablePositiveInt(['lines' => 0], 'lines', 'limits.file');
    }

    public function testRequiredStringRejectsMissingValue(): void
    {
        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('apply.default');

        (new ConfigScalarReader())->requiredString([], 'default', 'apply');
    }

    public function testOptionalStringReturnsConfiguredValueOrNull(): void
    {
        $reader = new ConfigScalarReader();

        self::assertSame('standard', $reader->optionalString(['extends' => 'standard'], 'extends', 'policies.native'));
        self::assertNull($reader->optionalString([], 'extends', 'policies.standard'));
    }
}
