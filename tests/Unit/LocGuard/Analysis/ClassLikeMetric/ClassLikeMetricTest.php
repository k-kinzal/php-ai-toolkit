<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\ClassLikeMetric;

use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetric
 */
#[CoversClass(ClassLikeMetric::class)]
final class ClassLikeMetricTest extends TestCase
{
    public function testLineCountIncludesStartAndEndLine(): void
    {
        $metric = new ClassLikeMetric('class', 'Example', 4, 8);

        self::assertSame(5, $metric->lineCount());
    }
}
