<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Execution\Statement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Statement::class)]
final class StatementTest extends TestCase
{
    public function testExposesTheCodeAndItsAssertion(): void
    {
        $statement = new Statement('divide(1, 0)', 'throws', 'DivisionByZeroError', 'Division by zero', 4);

        self::assertSame('divide(1, 0)', $statement->code);
        self::assertSame('throws', $statement->marker);
        self::assertSame('DivisionByZeroError', $statement->expected);
        self::assertSame('Division by zero', $statement->exceptionMessage);
        self::assertSame(4, $statement->line);
    }

    public function testExposesASmokeStatementWithoutAnAssertion(): void
    {
        $statement = new Statement('$ledger = new Ledger();', null, null, null, 1);

        self::assertNull($statement->marker);
        self::assertNull($statement->expected);
        self::assertNull($statement->exceptionMessage);
    }
}
