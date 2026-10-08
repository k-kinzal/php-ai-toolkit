<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\ScanConfigReader;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigStringListReader;
use Guard\Policy\Definition\ScanConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\ScanConfigReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Validation\MetricConfigKeyValidator
 * @uses \Guard\Config\Validation\MetricConfigStringListReader
 * @uses \Guard\Policy\Definition\ScanConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(ScanConfigReader::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(MetricConfigKeyValidator::class)]
#[UsesClass(MetricConfigStringListReader::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
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
