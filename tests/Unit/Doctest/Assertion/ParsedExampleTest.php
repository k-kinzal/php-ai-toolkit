<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Assertion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Assertion\ParsedExample;
use Toolkit\Doctest\Assertion\Statement;
use Toolkit\Doctest\Parser\Example;
use Toolkit\Doctest\Scanner\Target;
use Toolkit\Doctest\Scanner\TargetKind;

/**
 * @covers \Toolkit\Doctest\Assertion\ParsedExample
 * @uses \Toolkit\Doctest\Parser\Example
 * @uses \Toolkit\Doctest\Scanner\Target
 * @uses \Toolkit\Doctest\Assertion\Statement
 */
#[CoversClass(ParsedExample::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(Statement::class)]
final class ParsedExampleTest extends TestCase
{
    public function testKeepsTheExampleAndItsStatements(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 4);
        $example = new Example('1 + 1', $target, 6, 0);
        $statement = new Statement('1 + 1', null, 1);

        $parsed = new ParsedExample($example, [$statement]);

        self::assertSame($example, $parsed->example);
        self::assertSame([$statement], $parsed->statements);
    }
}
