<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Config\ReportConfig;
use Toolkit\DocGuard\Reporting\AiReporter;
use Toolkit\DocGuard\Reporting\AiReportGuidance;
use Toolkit\DocGuard\Reporting\AiReportSummary;
use Toolkit\DocGuard\Reporting\AiViolationAction;
use Toolkit\DocGuard\Reporting\AiViolationFormatter;
use Toolkit\DocGuard\Reporting\Reporter;
use Toolkit\DocGuard\Reporting\ViolationFieldComparator;
use Toolkit\DocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\DocGuard\Reporting\AiReporter
 * @uses \Toolkit\DocGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\DocGuard\Reporting\AiReportSummary
 * @uses \Toolkit\DocGuard\Reporting\AiViolationAction
 * @uses \Toolkit\DocGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\DocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Reporting\Reporter
 * @uses \Toolkit\DocGuard\Analysis\Violation
 * @uses \Toolkit\DocGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\DocGuard\Reporting\ViolationSorter
 */
#[CoversClass(AiReporter::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Reporter::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class AiReporterTest extends TestCase
{
    public function testReportFormatsPassingSummary(): void
    {
        $output = (new AiReporter())->report(new AnalysisResult(2, 10, []), new ReportConfig('ai', ['path', 'line', 'rule']));

        self::assertSame("DOC_GUARD_PASSED\nsummary:\n- documents: 2\n- headings: 10\n- violations: 0\n", $output);
    }

    public function testReportFormatsSortedViolationsWithGuidance(): void
    {
        $output = (new AiReporter())->report(
            new AnalysisResult(1, 3, [
                new Violation('README.md', 9, 'unexpected_heading', null, '## B', 'B.'),
                new Violation('README.md', 4, 'unexpected_heading', null, '## A', 'A.'),
            ]),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        );

        self::assertStringStartsWith("DOC_GUARD_FAILED\n", $output);
        self::assertStringContainsString('guidance:', $output);
        self::assertStringContainsString("1. README.md:4 [unexpected_heading]\n", $output);
        self::assertStringContainsString("2. README.md:9 [unexpected_heading]\n", $output);
    }
}
