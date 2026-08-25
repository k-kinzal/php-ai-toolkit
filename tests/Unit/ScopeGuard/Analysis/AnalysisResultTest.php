<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\AnalysisResult;
use Toolkit\ScopeGuard\Analysis\Violation;

/**
 * @covers \Toolkit\ScopeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\ScopeGuard\Analysis\Violation
 */
#[CoversClass(AnalysisResult::class)]
#[UsesClass(Violation::class)]
final class AnalysisResultTest extends TestCase
{
    public function testFileCountIsReadable(): void
    {
        self::assertSame(4, (new AnalysisResult(4, 2, 9, []))->fileCount);
    }

    public function testScopedDeclarationCountIsReadable(): void
    {
        self::assertSame(2, (new AnalysisResult(4, 2, 9, []))->scopedDeclarationCount);
    }

    public function testReferenceCountIsReadable(): void
    {
        self::assertSame(9, (new AnalysisResult(4, 2, 9, []))->referenceCount);
    }

    public function testViolationsAreReadable(): void
    {
        self::assertSame([], (new AnalysisResult(4, 2, 9, []))->violations);
    }


    public function testHasViolationsReportsAnEmptyResult(): void
    {
        self::assertFalse((new AnalysisResult(4, 2, 9, []))->hasViolations());
    }

    public function testHasViolationsReportsAFailedResult(): void
    {
        $violation = new Violation('src/Order.php', 3, 'out_of_scope', 'App\\Order', 'Not visible.');

        self::assertTrue((new AnalysisResult(4, 2, 9, [$violation]))->hasViolations());
    }

    public function testViolationCountCountsTheViolations(): void
    {
        $violation = new Violation('src/Order.php', 3, 'out_of_scope', 'App\\Order', 'Not visible.');

        self::assertSame(1, (new AnalysisResult(4, 2, 9, [$violation]))->violationCount());
    }
}
