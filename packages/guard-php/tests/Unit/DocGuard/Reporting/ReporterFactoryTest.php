<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Config\ReportConfig;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Reporting\AiReporter;
use Toolkit\DocGuard\Reporting\AiReportGuidance;
use Toolkit\DocGuard\Reporting\AiReportSummary;
use Toolkit\DocGuard\Reporting\AiViolationAction;
use Toolkit\DocGuard\Reporting\AiViolationFormatter;
use Toolkit\DocGuard\Reporting\JsonReporter;
use Toolkit\DocGuard\Reporting\ReporterFactory;
use Toolkit\DocGuard\Reporting\TextReporter;
use Toolkit\DocGuard\Reporting\ViolationFieldComparator;
use Toolkit\DocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\DocGuard\Reporting\ReporterFactory
 * @uses \Toolkit\DocGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\DocGuard\Reporting\AiReportSummary
 * @uses \Toolkit\DocGuard\Reporting\AiReporter
 * @uses \Toolkit\DocGuard\Reporting\AiViolationAction
 * @uses \Toolkit\DocGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\DocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Reporting\JsonReporter
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Reporting\TextReporter
 * @uses \Toolkit\DocGuard\Analysis\Violation
 * @uses \Toolkit\DocGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\DocGuard\Reporting\ViolationSorter
 */
#[CoversClass(ReporterFactory::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(DocGuardException::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(TextReporter::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class ReporterFactoryTest extends TestCase
{
    public function testCreateReturnsConfiguredReporter(): void
    {
        self::assertInstanceOf(AiReporter::class, (new ReporterFactory())->create('ai'));
        self::assertInstanceOf(TextReporter::class, (new ReporterFactory())->create('text'));
        self::assertInstanceOf(JsonReporter::class, (new ReporterFactory())->create('json'));
    }

    public function testCreateRejectsUnknownReporter(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Unknown DocGuard reporter: xml. Use one of: ai, text, json.');

        (new ReporterFactory())->create('xml');
    }
}
