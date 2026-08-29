<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigStringListReader;
use Toolkit\LocGuard\Config\ScanConfig;
use Toolkit\LocGuard\Config\ScanConfigReader;

/**
 * @covers \Toolkit\LocGuard\Config\ScanConfigReader
 */
#[CoversClass(ScanConfigReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ScanConfig::class)]
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
