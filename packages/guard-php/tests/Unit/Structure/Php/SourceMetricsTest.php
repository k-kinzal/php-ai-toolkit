<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\SourceMetrics
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Policy\Limit\ClassLikeMetricLimit
 * @uses \Guard\Policy\Limit\ClassLikeMetricViolationBuilder
 * @uses \Guard\Policy\Limit\FileMetricViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionMetricViolationBuilder
 * @uses \Guard\Policy\Limit\MetricLimitInspector
 * @uses \Guard\Reporting\MetricViolation
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Structure\Php\FileMetric\FileMetric
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 */
#[CoversClass(\Guard\Structure\Php\SourceMetrics::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Value\LimitConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\ClassLikeMetricLimit::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\ClassLikeMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FileMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionComplexityViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionLineViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\MetricLimitInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\MetricViolation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FileMetric\FileMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetric::class)]
final class SourceMetricsTest extends TestCase
{
    public function testUnreadableSourceDoesNotBecomeAThresholdViolation(): void
    {
        $subject = new \Guard\Structure\Php\SourceMetrics(new \Guard\Structure\Php\FileMetric\FileMetric('a.php', 100, 100), [], [], false);
        self::assertSame([], (new \Guard\Policy\Limit\MetricLimitInspector())->violations($subject, \Guard\Config\Value\LimitConfig::fromValues(['file.lines' => 1])));
    }

}
