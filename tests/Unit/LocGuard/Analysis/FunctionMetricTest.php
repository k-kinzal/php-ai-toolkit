<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\FunctionMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionMetric::class)]
final class FunctionMetricTest extends TestCase
{
    public function testLineCountIncludesStartAndEndLine(): void
    {
        $metric = new FunctionMetric('method', 'Example::run', 3, 7, 10, 20);

        self::assertSame(5, $metric->lineCount());
        self::assertSame(1, $metric->cyclomaticComplexity);
    }
}
