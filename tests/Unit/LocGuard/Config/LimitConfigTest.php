<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LimitConfig::class)]
final class LimitConfigTest extends TestCase
{
    public function testStoresNumericThresholds(): void
    {
        $config = new LimitConfig(500, 350, 400, 300, 200, 190, 50, 55, 20);

        self::assertSame(500, $config->maxFileLines);
        self::assertSame(350, $config->maxFileNcloc);
        self::assertSame(400, $config->maxClassLines);
        self::assertSame(300, $config->maxTraitLines);
        self::assertSame(200, $config->maxInterfaceLines);
        self::assertSame(190, $config->maxEnumLines);
        self::assertSame(50, $config->maxFunctionLines);
        self::assertSame(55, $config->maxMethodLines);
        self::assertSame(20, $config->maxCyclomaticComplexity);
    }
}
