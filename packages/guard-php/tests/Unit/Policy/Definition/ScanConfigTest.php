<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Definition;

use Guard\Policy\Definition\ScanConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Definition\ScanConfig
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
