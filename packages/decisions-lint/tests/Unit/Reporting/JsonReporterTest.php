<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Diagnostic\Severity;
use DecisionsLint\Lint\Report;
use DecisionsLint\Reporting\JsonReporter;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Reporting\JsonReporter
 * @uses \DecisionsLint\Diagnostic\Finding
 * @uses \DecisionsLint\Diagnostic\Severity
 * @uses \DecisionsLint\Lint\Report
 */
#[CoversClass(JsonReporter::class)]
#[UsesClass(Finding::class)]
#[UsesClass(Severity::class)]
#[UsesClass(Report::class)]
final class JsonReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRenderWritesFindingsSummaryAndSuccess(): void
    {
        $report = new Report([new Finding('src/A.php', 12, 5, 'cohesion.method', 'error', 'App\A::foo', 'Misplaced.', 'Move it.', 0.9)], 1, 2, 1, ['jev-1.13.0'], 'error');
        $decoded = json_decode((new JsonReporter())->render($report, false), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([
            'findings' => [['path' => 'src/A.php', 'line' => 12, 'column' => 5, 'rule' => 'cohesion.method', 'severity' => 'error', 'symbol' => 'App\A::foo', 'message' => 'Misplaced.', 'fix' => 'Move it.', 'confidence' => 0.9]],
            'summary' => ['error' => 1, 'warning' => 0, 'info' => 0],
            'files' => 1,
            'targets' => 2,
            'requests' => 1,
            'models' => ['jev-1.13.0'],
            'fail_on' => 'error',
            'success' => false,
        ], $decoded);
    }
}
