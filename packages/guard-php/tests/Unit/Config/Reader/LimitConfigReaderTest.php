<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\LimitConfigReader;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\LimitConfigReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Validation\MetricConfigKeyValidator
 * @uses \Guard\Config\Validation\MetricConfigScalarReader
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(LimitConfigReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(MetricConfigKeyValidator::class)]
#[UsesClass(MetricConfigScalarReader::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
        $this->expectException(PolicyException::class);

        (new LimitConfigReader())->read('strict');
    }

    public function testReadRejectsUnknownMetric(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('unsupported key "branches"');

        (new LimitConfigReader())->read(['method' => ['branches' => 10]]);
    }
}
