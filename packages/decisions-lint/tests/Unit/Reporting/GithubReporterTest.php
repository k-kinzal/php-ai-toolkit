<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Diagnostic\Severity;
use DecisionsLint\Lint\Report;
use DecisionsLint\Reporting\GithubReporter;
use DecisionsLint\Reporting\Summary;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Reporting\GithubReporter
 * @uses \DecisionsLint\Diagnostic\Finding
 * @uses \DecisionsLint\Diagnostic\Severity
 * @uses \DecisionsLint\Lint\Report
 * @uses \DecisionsLint\Reporting\Summary
 */
#[CoversClass(GithubReporter::class)]
#[UsesClass(Finding::class)]
#[UsesClass(Severity::class)]
#[UsesClass(Report::class)]
#[UsesClass(Summary::class)]
final class GithubReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRenderWritesWorkflowCommandsPerFinding(): void
    {
        $report = new Report([
            new Finding('src/A.php', 12, 5, 'cohesion.method', 'error', 'App\A::foo', "Misplaced,\nreally.", 'Move it: now.', 0.9),
            new Finding('src/A.php', 13, 1, 'naming.vague', 'info', 'App\A::bar', 'Vague.', 'Rename.', 0.6),
        ], 1, 2, 1, ['gpt-6-luna'], 'error');
        $text = (new GithubReporter(new Summary()))->render($report, false);
        self::assertStringContainsString("::error file=src/A.php,line=12,col=5,title=cohesion.method::Misplaced,%0Areally. Fix: Move it: now.\n", $text);
        self::assertStringContainsString("::notice file=src/A.php,line=13,col=1,title=naming.vague::Vague. Fix: Rename.\n", $text);
        self::assertStringEndsWith("1 error, 0 warnings, 1 info, 1 file with findings, 2 targets, 1 request to gpt-6-luna.\nDecisions check failed: a finding is at least error and fail_on is error.\n", $text);
        self::assertStringContainsString('::warning ', (new GithubReporter())->render(new Report([new Finding('a', 1, 1, 'r', 'warning', 's', 'm', 'f', 1.0)], 1, 1, 1, [], 'error'), false));
    }

    public function testPropertyEscapesSeparatorsAndNewlines(): void
    {
        self::assertSame('a%3Ab%2Cc%0A%25', (new GithubReporter())->property("a:b,c\n%"));
    }

    public function testDataEscapesNewlinesAndPercentOnly(): void
    {
        self::assertSame('a:b,c%0D%0A%25', (new GithubReporter())->data("a:b,c\r\n%"));
    }
}
