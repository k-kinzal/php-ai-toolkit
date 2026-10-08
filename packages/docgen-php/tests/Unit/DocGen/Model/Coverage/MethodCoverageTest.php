<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Model\Coverage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Model\Coverage\MethodCoverage;

/**
 * @covers \Toolkit\DocGen\Model\Coverage\MethodCoverage
 */
#[CoversClass(MethodCoverage::class)]
final class MethodCoverageTest extends TestCase
{
    public function testStoresCoverageFigures(): void
    {
        $coverage = new MethodCoverage(5, 4, 80.0);

        self::assertSame(5, $coverage->executable);
        self::assertSame(4, $coverage->executed);
        self::assertSame(80.0, $coverage->percent);
    }
}
