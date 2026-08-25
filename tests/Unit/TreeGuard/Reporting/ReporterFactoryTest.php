<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Reporting\AiReporter;
use Toolkit\TreeGuard\Reporting\AiReportGuidance;
use Toolkit\TreeGuard\Reporting\AiReportSummary;
use Toolkit\TreeGuard\Reporting\AiViolationAction;
use Toolkit\TreeGuard\Reporting\AiViolationFormatter;
use Toolkit\TreeGuard\Reporting\JsonReporter;
use Toolkit\TreeGuard\Reporting\ReporterFactory;
use Toolkit\TreeGuard\Reporting\TextReporter;
use Toolkit\TreeGuard\Reporting\ViolationFieldComparator;
use Toolkit\TreeGuard\Reporting\ViolationSorter;
use Toolkit\TreeGuard\TreeGuardException;

/**
 * @covers \Toolkit\TreeGuard\Reporting\ReporterFactory
 * @uses \Toolkit\TreeGuard\Reporting\AiReporter
 * @uses \Toolkit\TreeGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\TreeGuard\Reporting\AiReportSummary
 * @uses \Toolkit\TreeGuard\Reporting\AiViolationAction
 * @uses \Toolkit\TreeGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\TreeGuard\Reporting\JsonReporter
 * @uses \Toolkit\TreeGuard\Reporting\TextReporter
 * @uses \Toolkit\TreeGuard\TreeGuardException
 * @uses \Toolkit\TreeGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\TreeGuard\Reporting\ViolationSorter
 */
#[CoversClass(ReporterFactory::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(TextReporter::class)]
#[UsesClass(TreeGuardException::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class ReporterFactoryTest extends TestCase
{
    public function testCreateReturnsConfiguredReporters(): void
    {
        self::assertInstanceOf(AiReporter::class, (new ReporterFactory())->create('ai'));
        self::assertInstanceOf(TextReporter::class, (new ReporterFactory())->create('text'));
        self::assertInstanceOf(JsonReporter::class, (new ReporterFactory())->create('json'));
    }

    public function testCreateRejectsUnknownReporter(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Unknown TreeGuard reporter: xml');

        (new ReporterFactory())->create('xml');
    }
}
