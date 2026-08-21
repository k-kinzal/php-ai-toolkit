<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\Reporting\AiReporter;
use PhpAiToolkit\ScopeGuard\Reporting\AiReportGuidance;
use PhpAiToolkit\ScopeGuard\Reporting\AiReportSummary;
use PhpAiToolkit\ScopeGuard\Reporting\AiViolationAction;
use PhpAiToolkit\ScopeGuard\Reporting\AiViolationFormatter;
use PhpAiToolkit\ScopeGuard\Reporting\JsonReporter;
use PhpAiToolkit\ScopeGuard\Reporting\ReporterFactory;
use PhpAiToolkit\ScopeGuard\Reporting\TextReporter;
use PhpAiToolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use PhpAiToolkit\ScopeGuard\Reporting\ViolationSorter;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
