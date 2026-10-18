<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DecisionsLint\Lint\Report;
use DecisionsLint\Reporting\Reporter;
use JsonException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
#[CoversNothing]
final class ReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRenderReturnsTextEndingWithANewline(): void
    {
        $reporter = new class () implements Reporter {
            public function render(Report $report, bool $decorated): string
            {
                return ($decorated ? 'colour ' : '') . count($report->findings) . " findings\n";
            }
        };
        self::assertSame("0 findings\n", $reporter->render(new Report([], 0, 0, 0, [], 'error'), false));
        self::assertSame("colour 0 findings\n", $reporter->render(new Report([], 0, 0, 0, [], 'error'), true));
    }
}
