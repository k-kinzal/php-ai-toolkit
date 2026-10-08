<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting\Filtering;

use Guard\Diagnostic\Finding;
use Guard\Reporting\Filtering\ReportScope;

/**
 * @covers \Guard\Reporting\Filtering\ReportScope
 * @uses \Guard\Diagnostic\Finding
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ReportScope::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Finding::class)]
final class ReportScopeTest extends \PHPUnit\Framework\TestCase
{
    public function testNoteExplainsHiddenErrorsAndUnmatchedBaselineEntries(): void
    {
        $scope = new ReportScope([new Finding('a', 'r', 'required', 'Problem. Fix it.')], 'guard-baseline.json', 2, 1);
        self::assertStringContainsString('Showing 0 of 1 active findings', $scope->note(0));
        self::assertStringContainsString('2 suppressed, 1 unmatched', $scope->note(0));
        self::assertStringContainsString('remove resolved diagnostics', $scope->note(0));
        self::assertSame('', (new ReportScope([]))->note(0));
    }
}
