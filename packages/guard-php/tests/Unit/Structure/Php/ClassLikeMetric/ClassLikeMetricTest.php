<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php\ClassLikeMetric;

use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric
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
