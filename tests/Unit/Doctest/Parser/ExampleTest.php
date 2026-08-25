<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Parser\Example;
use Toolkit\Doctest\Scanner\Target;
use Toolkit\Doctest\Scanner\TargetKind;

/**
 * @covers \Toolkit\Doctest\Parser\Example
 * @uses \Toolkit\Doctest\Scanner\Target
 */
#[CoversClass(Example::class)]
#[UsesClass(Target::class)]
final class ExampleTest extends TestCase
{
    public function testGetNameCombinesTheTargetIndexAndDescription(): void
    {
        $target = new Target(TargetKind::METHOD, '/a.php', '/** */', 'add', 10, 'App', 'Calculator');
        $example = new Example('$calc->add(1, 2)', $target, 15, 0, 'Adding two numbers');

        self::assertSame('Calculator::add() example #1: Adding two numbers', $example->getName());
    }

    public function testGetNameLeavesTheDescriptionOutWhenThereIsNone(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 5);
        $example = new Example('new Calculator()', $target, 8, 1);

        self::assertSame('Calculator example #2', $example->getName());
    }

    public function testGetTestNameAddsTheSourceLine(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 5);
        $example = new Example('new Calculator()', $target, 8, 0);

        self::assertSame('Doctest: Calculator example #1 (line 8)', $example->getTestName());
    }

    public function testExposesWhatItWasBuiltFrom(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 5);
        $example = new Example('new Calculator()', $target, 8, 0, 'Building one');

        self::assertSame('new Calculator()', $example->code);
        self::assertSame($target, $example->target);
        self::assertSame(8, $example->lineNumber);
        self::assertSame(0, $example->index);
        self::assertSame('Building one', $example->description);
    }
}
