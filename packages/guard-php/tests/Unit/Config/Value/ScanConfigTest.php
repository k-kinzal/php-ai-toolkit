<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Value;

use Guard\Config\Value\ScanConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Value\ScanConfig
 */
#[CoversClass(ScanConfig::class)]
final class ScanConfigTest extends TestCase
{
    public function testStoresRootsAndExclusions(): void
    {
        $config = new ScanConfig(['src'], ['src/Generated/**']);

        self::assertSame(['src'], $config->roots);
        self::assertSame(['src/Generated/**'], $config->exclude);
    }
}
