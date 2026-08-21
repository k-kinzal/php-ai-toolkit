<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\Execution\SourceSyntax;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceSyntax::class)]
#[UsesClass(PhpParserBridge::class)]
final class SourceSyntaxTest extends TestCase
{
    public function testParseReturnsTheStatementsOfCompleteSource(): void
    {
        $statements = (new SourceSyntax())->parse('$sum = 1 + 2');

        self::assertNotNull($statements);
        self::assertCount(1, $statements);
        self::assertInstanceOf(\PhpParser\Node\Stmt\Expression::class, $statements[0]);
    }

    public function testParseReturnsNullForAnUnfinishedFragment(): void
    {
        self::assertNull((new SourceSyntax())->parse('final class Ledger extends Book'));
        self::assertNull((new SourceSyntax())->parse('add(1,'));
    }

    public function testParsesReportsWhetherTheFragmentStandsOnItsOwn(): void
    {
        $syntax = new SourceSyntax();

        self::assertTrue($syntax->parses('echo "hi";'));
        self::assertTrue($syntax->parses('foreach ([1] as $value) { echo $value; }'));
        self::assertFalse($syntax->parses('foreach ([1] as $value) {'));
    }
}
