<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc;

use Guard\Config\Loc\ConfigScalarReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\ConfigScalarReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ConfigScalarReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
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
        $this->expectException(PolicyException::class);

        (new ConfigScalarReader())->string(['reporter' => ''], 'reporter', 'ai');
    }

    public function testNullablePositiveIntRejectsNonPositiveInteger(): void
    {
        $this->expectException(PolicyException::class);

        (new ConfigScalarReader())->nullablePositiveInt(['lines' => 0], 'lines', 'limits.file');
    }

    public function testRequiredStringRejectsMissingValue(): void
    {
        $this->expectException(PolicyException::class);
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
