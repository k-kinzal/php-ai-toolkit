<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Diagnostic\Severity;
use DecisionsLint\Lint\Report;
use DecisionsLint\Reporting\Summary;
use DecisionsLint\Reporting\TextReporter;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Reporting\TextReporter
 * @uses \DecisionsLint\Diagnostic\Finding
 * @uses \DecisionsLint\Diagnostic\Severity
 * @uses \DecisionsLint\Lint\Report
 * @uses \DecisionsLint\Reporting\Summary
 */
#[CoversClass(TextReporter::class)]
#[UsesClass(Finding::class)]
#[UsesClass(Severity::class)]
#[UsesClass(Report::class)]
#[UsesClass(Summary::class)]
final class TextReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRenderGroupsFindingsByFileWithPositionMessageAndFix(): void
    {
        $report = new Report([
            new Finding('src/A.php', 12, 5, 'cohesion.method', 'error', 'App\A::foo', 'Method foo() <misplaced>.', 'Move foo().', 0.91),
            new Finding('src/B.php', 3, 1, 'naming.vague', 'info', 'App\B::doIt', 'Vague.', 'Rename.', 0.5),
        ], 2, 4, 3, ['jev-1.13.0'], 'error');
        $text = (new TextReporter(new Summary()))->render($report, false);
        self::assertStringContainsString("\nDecisions check\n", $text);
        self::assertStringContainsString("src/A.php\n  12:5 error cohesion.method App\A::foo (confidence 0.91)\n    Method foo() <misplaced>.\n    Fix: Move foo().\n", $text);
        self::assertStringContainsString("src/B.php\n  3:1 info naming.vague", $text);
        self::assertStringContainsString('1 error · 0 warnings · 1 info · 2 files with findings · 4 targets · 3 requests to jev-1.13.0', $text);
        self::assertStringEndsWith("Decisions check failed: a finding is at least error and fail_on is error.\n", $text);
        self::assertStringNotContainsString("\033[", $text);
    }

    /**
     * @throws JsonException
     */
    public function testRenderColoursTheOutputWhenDecorated(): void
    {
        $report = new Report([new Finding('src/A.php', 1, 1, 'r', 'warning', 's', 'm', 'f', 1.0)], 1, 1, 1, [], 'error');
        $text = (new TextReporter())->render($report, true);
        self::assertStringContainsString("\033[", $text);
        self::assertStringContainsString('Decisions check passed.', $text);
    }

    public function testColorMapsSeverities(): void
    {
        $reporter = new TextReporter();
        self::assertSame('red', $reporter->color('error'));
        self::assertSame('yellow', $reporter->color('warning'));
        self::assertSame('blue', $reporter->color('info'));
    }
}
