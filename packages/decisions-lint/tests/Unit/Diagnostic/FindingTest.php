<?php

declare(strict_types=1);

namespace Tests\Unit\Diagnostic;

use DecisionsLint\Diagnostic\Finding;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Diagnostic\Finding
 */
#[CoversClass(Finding::class)]
final class FindingTest extends TestCase
{
    public function testExposesThePositionTheRuleAndTheFix(): void
    {
        $finding = new Finding('src/Analyzer.php', 12, 5, 'cohesion.method-belongs-to-class', 'warning', 'App\Analyzer::foo', 'Method foo() does not belong to Analyzer.', 'Move foo() elsewhere.', 0.91);
        self::assertSame('src/Analyzer.php', $finding->path);
        self::assertSame(12, $finding->line);
        self::assertSame(5, $finding->column);
        self::assertSame('cohesion.method-belongs-to-class', $finding->rule);
        self::assertSame('warning', $finding->severity);
        self::assertSame('App\Analyzer::foo', $finding->symbol);
        self::assertSame('Method foo() does not belong to Analyzer.', $finding->message);
        self::assertSame('Move foo() elsewhere.', $finding->fix);
        self::assertSame(0.91, $finding->confidence);
    }
}
