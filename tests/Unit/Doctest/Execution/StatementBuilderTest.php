<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Analysis\AssertionLine;
use PhpAiToolkit\Doctest\Analysis\AssertionScanner;
use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\Execution\SourceSyntax;
use PhpAiToolkit\Doctest\Execution\Statement;
use PhpAiToolkit\Doctest\Execution\StatementBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StatementBuilder::class)]
#[UsesClass(Statement::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssertionLine::class)]
#[UsesClass(SourceSyntax::class)]
#[UsesClass(PhpParserBridge::class)]
final class StatementBuilderTest extends TestCase
{
    public function testBuildSplitsOneStatementPerLineAndKeepsAssertions(): void
    {
        $statements = (new StatementBuilder())->build("\$sum = add(1, 2);\n\$sum // => 3");

        self::assertCount(2, $statements);
        self::assertSame('$sum = add(1, 2);', $statements[0]->code);
        self::assertNull($statements[0]->marker);
        self::assertSame(1, $statements[0]->line);
        self::assertSame('$sum', $statements[1]->code);
        self::assertSame('return', $statements[1]->marker);
        self::assertSame('3', $statements[1]->expected);
        self::assertSame(2, $statements[1]->line);
    }

    public function testBuildJoinsACallSpreadOverSeveralLines(): void
    {
        $statements = (new StatementBuilder())->build("\$calculator->add(\n    10,\n    5\n) // => 15");

        self::assertCount(1, $statements);
        self::assertSame('$calculator->add( 10, 5 )', $statements[0]->code);
        self::assertSame('return', $statements[0]->marker);
        self::assertSame(1, $statements[0]->line);
    }

    public function testBuildHoldsADeclarationTogetherUntilItParses(): void
    {
        $statements = (new StatementBuilder())->build("final class Ledger\n{\n    public \$open = true;\n}");

        self::assertCount(1, $statements);
        self::assertSame('final class Ledger { public $open = true; }', $statements[0]->code);
    }

    public function testBuildSkipsBlankLinesAndFlushesWhatIsLeftOver(): void
    {
        $statements = (new StatementBuilder())->build("\n\$sum = 1;\n\nadd(");

        self::assertCount(2, $statements);
        self::assertSame('$sum = 1;', $statements[0]->code);
        self::assertSame('add(', $statements[1]->code);
        self::assertSame(4, $statements[1]->line);
    }

    public function testCompleteRequiresBalancedBracketsAndParsableSource(): void
    {
        $builder = new StatementBuilder();

        self::assertTrue($builder->complete('$sum = 1;'));
        self::assertFalse($builder->complete('add(1,'));
        self::assertFalse($builder->complete('$sum = 1 +'));
        self::assertFalse($builder->complete('final class Ledger'));
    }

    public function testDanglingDetectsAnOperatorLeftWithoutARightHandSide(): void
    {
        $builder = new StatementBuilder();

        self::assertTrue($builder->dangling('$sum = 1 +'));
        self::assertTrue($builder->dangling('$ledger->'));
        self::assertFalse($builder->dangling('$sum = 1;'));
    }

    public function testDepthCountsUnclosedBracketsAndIgnoresStrings(): void
    {
        $builder = new StatementBuilder();

        self::assertSame(0, $builder->depth('add(1, 2)'));
        self::assertSame(1, $builder->depth('add('));
        self::assertSame(2, $builder->depth('[ ['));
        self::assertSame(0, $builder->depth('$url = "https://example.com/(";'));
    }
}
