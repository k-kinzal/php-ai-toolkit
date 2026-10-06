<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Config\Loc\ConfigStringListReader;
use Guard\Config\Loc\ScanConfig;
use Guard\Config\Loc\ScanConfigReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\ScanConfigReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\ConfigKeyValidator
 * @uses \Guard\Config\Loc\ConfigStringListReader
 * @uses \Guard\Config\Loc\ScanConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ScanConfigReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
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
