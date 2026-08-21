<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
final class ExampleTest extends TestCase
{
    public function testIdCombinesTheSymbolWithTheOneBasedExampleNumber(): void
    {
        $example = new Example(new Target(Target::METHOD, '/app/src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger'), new DocExample(null, 'append()', 'tag', 1), 14);

        self::assertSame('App\Ledger::append()#2', $example->id());
    }

    public function testNameReadsAsAHeadingWithTheDescription(): void
    {
        $described = new Example(new Target(Target::METHOD, '/app/src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger'), new DocExample('Appending an entry', 'append()', 'tag', 0), 14);
        $plain = new Example(new Target(Target::METHOD, '/app/src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger'), new DocExample(null, 'append()', 'tag', 0), 14);

        self::assertSame('Ledger::append() example #1: Appending an entry', $described->name());
        self::assertSame('Ledger::append() example #1', $plain->name());
    }

    public function testCodeReturnsTheExampleBody(): void
    {
        self::assertSame('append()', (new Example(new Target(Target::METHOD, '/app/src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger'), new DocExample(null, 'append()', 'tag', 0), 14))->code());
    }

    public function testRunnableIsFalseOnlyForDisplayOnlyExamples(): void
    {
        self::assertTrue((new Example(new Target(Target::METHOD, '/app/src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger'), new DocExample(null, 'append()', 'tag', 0), 14))->runnable());
        self::assertTrue((new Example(new Target(Target::METHOD, '/app/src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger'), new DocExample(null, 'append()', 'fence', 0), 14))->runnable());
        self::assertFalse((new Example(new Target(Target::METHOD, '/app/src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger'), new DocExample(null, 'append()', 'tag-inline', 0), 14))->runnable());
    }

    public function testExposesTheTargetAndTheExtractedExample(): void
    {
        $target = new Target(Target::METHOD, '/app/src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger');
        $doc = new DocExample(null, 'append()', 'tag', 0);
        $example = new Example($target, $doc, 14);

        self::assertSame($target, $example->target);
        self::assertSame($doc, $example->example);
        self::assertSame(14, $example->line);
    }
}
