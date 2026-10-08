<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Limit;

use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Limit\ClassLikeMetricLimit;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Limit\ClassLikeMetricLimit
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric
 */
#[CoversClass(ClassLikeMetricLimit::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(ClassLikeMetric::class)]
final class ClassLikeMetricLimitTest extends TestCase
{
    public function testLimitReturnsClassLikeSpecificLimit(): void
    {
        $limits = new LimitConfig(100, 100, 10, 11, 12, 13, 50, 50, 50, 50);
        $limit = new ClassLikeMetricLimit();

        self::assertSame(10, $limit->limit(new ClassLikeMetric('class', 'Example', 1, 3), $limits));
        self::assertSame(11, $limit->limit(new ClassLikeMetric('trait', 'Behavior', 1, 3), $limits));
        self::assertSame(12, $limit->limit(new ClassLikeMetric('interface', 'Contract', 1, 3), $limits));
        self::assertSame(13, $limit->limit(new ClassLikeMetric('enum', 'Status', 1, 3), $limits));
    }
}
