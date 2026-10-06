<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Php;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Php\SourceMetrics
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Collect\Php\FileMetric\FileMetric
 * @uses \Guard\Collect\Php\Filesystem\FilePathPatternMatcher
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Collect\Php\PhpSources
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\MetricsConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\ScanConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\Loc\Assignment\ApplyRuleMatcher
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssigner
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssignment
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \Guard\Policy\Loc\LocPolicy
 * @uses \Guard\Policy\Loc\Violation
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Collect\Php\SourceMetrics::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FileMetric\FileMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\Filesystem\FilePathPatternMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Php\PhpSources::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\LimitConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\MetricsConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\Policy\PolicyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Loc\ScanConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\ApplyRuleMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\FilePolicyAssigner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Assignment\FilePolicyAssignment::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\LocPolicy::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Loc\Violation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class SourceMetricsTest extends TestCase
{
    public function testUnreadableSourceDoesNotBecomeAThresholdViolation(): void
    {
        $subject = new \Guard\Collect\Php\SourceMetrics(new \Guard\Collect\Php\FileMetric\FileMetric('a.php', 100, 100), [], [], false);
        self::assertSame([], (new \Guard\Policy\Loc\LocPolicy())->violations($subject, \Guard\Config\Loc\LimitConfig::fromValues(['file.lines' => 1])));
    }

}
