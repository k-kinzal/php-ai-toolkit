<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ScanConfig;

/**
 * @covers \Toolkit\LocGuard\Config\ScanConfig
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
