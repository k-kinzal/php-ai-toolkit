<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Assertion;

use PhpAiToolkit\Doctest\Assertion\Assertion;
use PhpAiToolkit\Doctest\Assertion\AssertionKind;
use PhpAiToolkit\Doctest\Assertion\Statement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Statement::class)]
#[UsesClass(Assertion::class)]
final class StatementTest extends TestCase
{
    public function testHasAssertionIsTrueForAnAssertedStatement(): void
    {
        $statement = new Statement('1 + 2', new Assertion(AssertionKind::RETURN_VALUE, '3'), 5);

        self::assertTrue($statement->hasAssertion());
        self::assertSame('1 + 2', $statement->code);
        self::assertSame(5, $statement->line);
        self::assertNotNull($statement->assertion);
    }

    public function testHasAssertionIsFalseForASmokeTest(): void
    {
        $statement = new Statement('$x = 1;', null, 10);

        self::assertFalse($statement->hasAssertion());
        self::assertNull($statement->assertion);
    }

    public function testReadingAPropertyItDoesNotDeclareYieldsNull(): void
    {
        self::assertNull((new Statement('1 + 2', null, 5))->statements);
    }
}
