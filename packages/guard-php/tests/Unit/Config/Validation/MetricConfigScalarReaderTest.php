<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\MetricConfigScalarReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(MetricConfigScalarReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
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
