<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\ScanConfigReader;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigStringListReader;
use Guard\Config\Value\ScanConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\ScanConfigReader
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
 * @uses \Guard\Config\Validation\MetricConfigStringListReader
 * @uses \Guard\Config\Value\ScanConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ScanConfigReader::class)]
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
#[UsesClass(MetricConfigStringListReader::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class ScanConfigReaderTest extends TestCase
{
    public function testReadReturnsSourceDiscoveryConfiguration(): void
    {
        $config = (new ScanConfigReader())->read([
            'roots' => ['src'],
            'exclude' => ['src/Generated/**'],
        ]);

        self::assertSame(['src'], $config->roots);
        self::assertSame(['src/Generated/**'], $config->exclude);
    }
}
