<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Reporting\AiReporter;
use Toolkit\ScopeGuard\Reporting\AiReportGuidance;
use Toolkit\ScopeGuard\Reporting\AiReportSummary;
use Toolkit\ScopeGuard\Reporting\AiViolationAction;
use Toolkit\ScopeGuard\Reporting\AiViolationFormatter;
use Toolkit\ScopeGuard\Reporting\JsonReporter;
use Toolkit\ScopeGuard\Reporting\ReporterFactory;
use Toolkit\ScopeGuard\Reporting\TextReporter;
use Toolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use Toolkit\ScopeGuard\Reporting\ViolationSorter;
use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * @covers \Toolkit\ScopeGuard\Reporting\ReporterFactory
 * @uses \Toolkit\ScopeGuard\Reporting\AiReporter
 * @uses \Toolkit\ScopeGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\ScopeGuard\Reporting\AiReportSummary
 * @uses \Toolkit\ScopeGuard\Reporting\AiViolationAction
 * @uses \Toolkit\ScopeGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\ScopeGuard\Reporting\JsonReporter
 * @uses \Toolkit\ScopeGuard\ScopeGuardException
 * @uses \Toolkit\ScopeGuard\Reporting\TextReporter
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationSorter
 */
#[CoversClass(ReporterFactory::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(ScopeGuardException::class)]
#[UsesClass(TextReporter::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class ReporterFactoryTest extends TestCase
{
    /**
     * @throws ScopeGuardException
     */
    public function testCreateReturnsTheAiReporter(): void
    {
        self::assertInstanceOf(AiReporter::class, (new ReporterFactory())->create('ai'));
    }

    /**
     * @throws ScopeGuardException
     */
    public function testCreateReturnsTheTextReporter(): void
    {
        self::assertInstanceOf(TextReporter::class, (new ReporterFactory())->create('text'));
    }

    /**
     * @throws ScopeGuardException
     */
    public function testCreateReturnsTheJsonReporter(): void
    {
        self::assertInstanceOf(JsonReporter::class, (new ReporterFactory())->create('json'));
    }

    /**
     * @throws ScopeGuardException
     */
    public function testCreateRejectsAnUnknownReporter(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ReporterFactory())->create('xml');
    }
}
