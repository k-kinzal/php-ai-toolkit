<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Diagnostic\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\MetricConfigScalarReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(MetricConfigScalarReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
final class MetricConfigScalarReaderTest extends TestCase
{
    public function testStringReturnsNonEmptyString(): void
    {
        self::assertSame('ai', (new MetricConfigScalarReader())->string(['reporter' => 'ai'], 'reporter', 'text'));
    }

    public function testNullablePositiveIntReturnsPositiveIntegerAndNull(): void
    {
        $reader = new MetricConfigScalarReader();

        self::assertSame(10, $reader->nullablePositiveInt(['lines' => 10], 'lines', 'limits.file'));
        self::assertNull($reader->nullablePositiveInt(['lines' => null], 'lines', 'limits.file'));
    }

    public function testStringRejectsEmptyString(): void
    {
        $this->expectException(PolicyException::class);

        (new MetricConfigScalarReader())->string(['reporter' => ''], 'reporter', 'ai');
    }

    public function testNullablePositiveIntRejectsNonPositiveInteger(): void
    {
        $this->expectException(PolicyException::class);

        (new MetricConfigScalarReader())->nullablePositiveInt(['lines' => 0], 'lines', 'limits.file');
    }

    public function testRequiredStringRejectsMissingValue(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('apply.default');

        (new MetricConfigScalarReader())->requiredString([], 'default', 'apply');
    }

    public function testOptionalStringReturnsConfiguredValueOrNull(): void
    {
        $reader = new MetricConfigScalarReader();

        self::assertSame('standard', $reader->optionalString(['extends' => 'standard'], 'extends', 'policies.native'));
        self::assertNull($reader->optionalString([], 'extends', 'policies.standard'));
    }
}
