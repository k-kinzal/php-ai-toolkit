<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Config\ReportConfig;
use Toolkit\DocGuard\Reporting\Reporter;
use Toolkit\DocGuard\Reporting\TextReporter;
use Toolkit\DocGuard\Reporting\ViolationFieldComparator;
use Toolkit\DocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\DocGuard\Reporting\TextReporter
 * @uses \Toolkit\DocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Reporting\Reporter
 * @uses \Toolkit\DocGuard\Analysis\Violation
 * @uses \Toolkit\DocGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\DocGuard\Reporting\ViolationSorter
 */
#[CoversClass(TextReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Reporter::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class TextReporterTest extends TestCase
{
    public function testReportFormatsPassingSummary(): void
    {
        $output = (new TextReporter())->report(new AnalysisResult(2, 10, []), new ReportConfig('text', ['path']));

        self::assertSame("DocGuard passed. No violations found.\nSummary: 2 documents, 10 headings.\n", $output);
    }

    public function testReportListsViolations(): void
    {
        $output = (new TextReporter())->report(
            new AnalysisResult(2, 10, [
                new Violation('docs/x.md', null, 'undeclared_document', null, null, 'Undeclared.'),
                new Violation('README.md', 4, 'unexpected_heading', null, '## A', 'Added.'),
            ]),
            new ReportConfig('text', ['path']),
        );

        self::assertStringStartsWith("DocGuard found 2 violations.\nSummary: 2 documents, 10 headings.\n", $output);
        self::assertStringContainsString("\nREADME.md:4 [unexpected_heading]\n  Added.\n\ndocs/x.md [undeclared_document]\n  Undeclared.\n", $output);
    }
}
