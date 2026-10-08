<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Validation\MetricConfigStringListReader;
use Guard\Diagnostic\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\MetricConfigStringListReader
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
#[CoversClass(MetricConfigStringListReader::class)]
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
final class MetricConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsConfiguredStringList(): void
    {
        self::assertSame(['src'], (new MetricConfigStringListReader())->read(['paths' => ['src']], 'paths', []));
    }

    public function testReadRejectsInvalidStringList(): void
    {
        $this->expectException(PolicyException::class);

        (new MetricConfigStringListReader())->read(['paths' => [1]], 'paths', []);
    }

    public function testReadRequiredRejectsEmptyList(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('must contain at least one entry');

        (new MetricConfigStringListReader())->readRequired(['roots' => []], 'roots', 'scan', false);
    }
}
