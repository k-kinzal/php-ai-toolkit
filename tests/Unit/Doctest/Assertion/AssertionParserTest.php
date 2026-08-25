<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Assertion;

use PhpAiToolkit\Doctest\Assertion\Assertion;
use PhpAiToolkit\Doctest\Assertion\AssertionKind;
use PhpAiToolkit\Doctest\Assertion\AssertionParser;
use PhpAiToolkit\Doctest\Assertion\ParsedExample;
use PhpAiToolkit\Doctest\Assertion\Statement;
use PhpAiToolkit\Doctest\Parser\Example;
use PhpAiToolkit\Doctest\Scanner\Target;
use PhpAiToolkit\Doctest\Scanner\TargetKind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\Doctest\Assertion\AssertionParser
 * @uses \PhpAiToolkit\Doctest\Assertion\Statement
 * @uses \PhpAiToolkit\Doctest\Assertion\Assertion
 * @uses \PhpAiToolkit\Doctest\Assertion\ParsedExample
 * @uses \PhpAiToolkit\Doctest\Parser\Example
 * @uses \PhpAiToolkit\Doctest\Scanner\Target
 */
#[CoversClass(AssertionParser::class)]
#[UsesClass(Statement::class)]
#[UsesClass(Assertion::class)]
#[UsesClass(ParsedExample::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
final class AssertionParserTest extends TestCase
{
    public function testParseReadsAReturnValueAssertion(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 1);
        $parsed = (new AssertionParser())->parse(new Example('add(1, 2) // => 3', $target, 1, 0));

        $assertion = $parsed->statements[0]->assertion;

        self::assertCount(1, $parsed->statements);
        self::assertSame('add(1, 2)', $parsed->statements[0]->code);
        self::assertNotNull($assertion);
        self::assertSame(AssertionKind::RETURN_VALUE, $assertion->type);
        self::assertSame('3', $assertion->expectedRaw);
    }

    public function testParseReadsOutputAndExceptionAssertions(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 1);
        $code = "echo greet(); // Output: Hello\nboom(); // throws RuntimeException: bad";

        $parsed = (new AssertionParser())->parse(new Example($code, $target, 1, 0));

        $output = $parsed->statements[0]->assertion;
        $exception = $parsed->statements[1]->assertion;

        self::assertNotNull($output);
        self::assertNotNull($exception);
        self::assertSame(AssertionKind::OUTPUT, $output->type);
        self::assertSame('Hello', $output->expectedRaw);
        self::assertSame(AssertionKind::EXCEPTION, $exception->type);
        self::assertSame('RuntimeException', $exception->expectedRaw);
        self::assertSame('bad', $exception->exceptionMessage);
    }

    public function testParseTreatsAPlainLineAsASmokeStatementAndSkipsBlankLines(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 1);

        $parsed = (new AssertionParser())->parse(new Example("\n\$sum = 1;\n\n", $target, 1, 0));

        self::assertCount(1, $parsed->statements);
        self::assertSame('$sum = 1;', $parsed->statements[0]->code);
        self::assertNull($parsed->statements[0]->assertion);
        self::assertSame(2, $parsed->statements[0]->line);
    }

    public function testParseJoinsALineThatIsContinuedOnTheNext(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 1);

        $parsed = (new AssertionParser())->parse(new Example("add(\n1, 2) // => 3", $target, 1, 0));

        self::assertCount(1, $parsed->statements);
        self::assertSame('add( 1, 2)', $parsed->statements[0]->code);
    }

    public function testParseFlushesCodeLeftBufferedAtTheEnd(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 1);

        $parsed = (new AssertionParser())->parse(new Example('add(', $target, 1, 0));

        self::assertCount(1, $parsed->statements);
        self::assertSame('add(', $parsed->statements[0]->code);
        self::assertNull($parsed->statements[0]->assertion);
    }

    public function testParseTrimsEachLineBeforeReadingIt(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 1);

        $parsed = (new AssertionParser())->parse(new Example("   \n    \$sum = 1;", $target, 1, 0));

        self::assertCount(1, $parsed->statements);
        self::assertSame('$sum = 1;', $parsed->statements[0]->code);
    }

    public function testParseKeepsBufferingWhileTheLinesStayIncomplete(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 1);

        $parsed = (new AssertionParser())->parse(new Example("add(\n1,\n2)", $target, 1, 0));

        self::assertCount(1, $parsed->statements);
        self::assertSame('add( 1, 2)', $parsed->statements[0]->code);
    }

    public function testParseLineReturnsNullWhenTheLineCarriesNoAssertion(): void
    {
        self::assertNull((new AssertionParser())->parseLine('$sum = 1;', '', 1));
    }

    public function testParseLineTrimsTheCodeAndTheValueOfAReturnAssertion(): void
    {
        self::assertEquals(
            new Statement('pre add(1, 2)', new Assertion(AssertionKind::RETURN_VALUE, '3'), 7),
            (new AssertionParser())->parseLine('  add(1, 2)   // =>   3  ', 'pre ', 7),
        );
    }

    public function testParseLineTakesTheOutputOfAnOutputAssertionAsWritten(): void
    {
        self::assertEquals(
            new Statement('pre echo $x;', new Assertion(AssertionKind::OUTPUT, 'hi'), 4),
            (new AssertionParser())->parseLine('  echo $x; // Output: hi', 'pre ', 4),
        );
    }

    public function testParseLineTrimsTheMessageOfAnExceptionAssertion(): void
    {
        self::assertEquals(
            new Statement('pre boom();', new Assertion(AssertionKind::EXCEPTION, 'RuntimeException', 'bad'), 2),
            (new AssertionParser())->parseLine('  boom(); // throws RuntimeException:   bad  ', 'pre ', 2),
        );
    }

    public function testParseLineLeavesTheMessageOutOfAnExceptionAssertionThatNamesNone(): void
    {
        self::assertEquals(
            new Statement('boom();', new Assertion(AssertionKind::EXCEPTION, 'RuntimeException'), 1),
            (new AssertionParser())->parseLine('boom(); // throws RuntimeException', '', 1),
        );
    }

    public function testIsIncompleteLineDetectsADanglingOperator(): void
    {
        $parser = new AssertionParser();

        self::assertTrue($parser->isIncompleteLine('add('));
        self::assertTrue($parser->isIncompleteLine('$value ->'));
        self::assertFalse($parser->isIncompleteLine('$sum = 1;'));
    }

    /**
     * @dataProvider providerContinuedLine
     */
    #[DataProvider('providerContinuedLine')]
    public function testIsIncompleteLineDetectsEveryContinuationItKnows(string $line): void
    {
        self::assertTrue((new AssertionParser())->isIncompleteLine($line));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function providerContinuedLine(): array
    {
        return [
            'concatenation' => ['$greeting = "Hello" .'],
            'argument list' => ['add(1,'],
            'call' => ['add('],
            'array literal' => ['$values = ['],
            'block' => ['if (true) {'],
            'array key' => ['$values = [1 =>'],
            'object access' => ['$widget ->'],
            'static access' => ['Widget::'],
            'conjunction' => ['$left &&'],
            'disjunction' => ['$left ||'],
            'ternary' => ['$left ?'],
        ];
    }
}
