<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Assertion;

use PhpAiToolkit\Doctest\Assertion\Assertion;
use PhpAiToolkit\Doctest\Assertion\AssertionKind;
use PhpAiToolkit\Doctest\Assertion\AssertionResult;
use PhpAiToolkit\Doctest\Assertion\Statement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \PhpAiToolkit\Doctest\Assertion\AssertionResult
 * @uses \PhpAiToolkit\Doctest\Assertion\Statement
 * @uses \PhpAiToolkit\Doctest\Assertion\Assertion
 */
#[CoversClass(AssertionResult::class)]
#[UsesClass(Statement::class)]
#[UsesClass(Assertion::class)]
final class AssertionResultTest extends TestCase
{
    public function testGetDetailedMessageIsOkForAPassingResult(): void
    {
        $result = new AssertionResult(true, '', new Statement('1 + 1', null, 1));

        self::assertTrue($result->passed);
        self::assertSame('OK', $result->getDetailedMessage());
    }

    public function testGetDetailedMessageDescribesAFailingResult(): void
    {
        $statement = new Statement('1 + 1', new Assertion(AssertionKind::RETURN_VALUE, '3'), 1);
        $result = new AssertionResult(false, 'Values do not match', $statement, 3, 2);

        self::assertSame(
            "Assertion failed\n  Code: 1 + 1\n  Expected: 3\n  Actual: 2\n  Message: Values do not match",
            $result->getDetailedMessage(),
        );
        self::assertSame($statement, $result->statement);
        self::assertSame(3, $result->expected);
        self::assertSame(2, $result->actual);
        self::assertSame('Values do not match', $result->message);
    }

    public function testGetDetailedMessageLeavesOutWhatTheResultDoesNotCarry(): void
    {
        $statement = new Statement('run()', null, 1);

        self::assertSame("Assertion failed\n  Code: run()", (new AssertionResult(false, '', $statement))->getDetailedMessage());
        self::assertSame("Assertion failed\n  Code: run()\n  Expected: 3", (new AssertionResult(false, '', $statement, 3))->getDetailedMessage());
        self::assertSame("Assertion failed\n  Code: run()\n  Actual: 2", (new AssertionResult(false, '', $statement, null, 2))->getDetailedMessage());
    }

    public function testFormatValueRendersEachKindOfValue(): void
    {
        $result = new AssertionResult(true, '', new Statement('x', null, 1));

        self::assertSame('"text"', $result->formatValue('text'));
        self::assertSame('true', $result->formatValue(true));
        self::assertSame('false', $result->formatValue(false));
        self::assertSame('null', $result->formatValue(null));
        self::assertSame('42', $result->formatValue(42));
        self::assertSame('1.5', $result->formatValue(1.5));
        self::assertSame(stdClass::class, $result->formatValue(new stdClass()));
        self::assertStringContainsString('0 => 1', $result->formatValue([1]));
    }
}
