<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Analysis\Violation;

/**
 * @covers \Toolkit\DocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\DocGuard\Analysis\Violation
 */
#[CoversClass(AnalysisResult::class)]
#[UsesClass(Violation::class)]
final class AnalysisResultTest extends TestCase
{
    public function testGetExposesCountsAndViolations(): void
    {
        $violations = [new Violation('README.md', null, 'missing_document', null, null, 'Missing.')];
        $result = new AnalysisResult(3, 12, $violations);

        self::assertSame(3, $result->documents);
        self::assertSame(12, $result->headings);
        self::assertSame($violations, $result->violations);
    }

    public function testHasViolationsReflectsViolationList(): void
    {
        self::assertFalse((new AnalysisResult(1, 1, []))->hasViolations());
        self::assertTrue((new AnalysisResult(1, 1, [new Violation('README.md', null, 'missing_document', null, null, 'Missing.')]))->hasViolations());
    }

    public function testViolationCountCountsViolations(): void
    {
        self::assertSame(0, (new AnalysisResult(1, 1, []))->violationCount());
        self::assertSame(1, (new AnalysisResult(1, 1, [new Violation('README.md', null, 'missing_document', null, null, 'Missing.')]))->violationCount());
    }
}
