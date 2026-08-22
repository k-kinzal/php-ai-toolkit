<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Assertion;

use PhpAiToolkit\Doctest\Assertion\ParsedExample;
use PhpAiToolkit\Doctest\Assertion\Statement;
use PhpAiToolkit\Doctest\Parser\Example;
use PhpAiToolkit\Doctest\Scanner\Target;
use PhpAiToolkit\Doctest\Scanner\TargetKind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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

    public function testReadingAPropertyItDoesNotDeclareYieldsNull(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 4);

        self::assertNull((new ParsedExample(new Example('1 + 1', $target, 6, 0), []))->code);
    }
}
