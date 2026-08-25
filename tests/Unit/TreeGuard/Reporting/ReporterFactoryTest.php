<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PhpAiToolkit\TreeGuard\Reporting\AiReporter;
use PhpAiToolkit\TreeGuard\Reporting\AiReportGuidance;
use PhpAiToolkit\TreeGuard\Reporting\AiReportSummary;
use PhpAiToolkit\TreeGuard\Reporting\AiViolationAction;
use PhpAiToolkit\TreeGuard\Reporting\AiViolationFormatter;
use PhpAiToolkit\TreeGuard\Reporting\JsonReporter;
use PhpAiToolkit\TreeGuard\Reporting\ReporterFactory;
use PhpAiToolkit\TreeGuard\Reporting\TextReporter;
use PhpAiToolkit\TreeGuard\Reporting\ViolationFieldComparator;
use PhpAiToolkit\TreeGuard\Reporting\ViolationSorter;
use PhpAiToolkit\TreeGuard\TreeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\TreeGuard\Reporting\ReporterFactory
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiReporter
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiReportGuidance
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiReportSummary
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiViolationAction
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiViolationFormatter
 * @uses \PhpAiToolkit\TreeGuard\Reporting\JsonReporter
 * @uses \PhpAiToolkit\TreeGuard\Reporting\TextReporter
 * @uses \PhpAiToolkit\TreeGuard\TreeGuardException
 * @uses \PhpAiToolkit\TreeGuard\Reporting\ViolationFieldComparator
 * @uses \PhpAiToolkit\TreeGuard\Reporting\ViolationSorter
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
