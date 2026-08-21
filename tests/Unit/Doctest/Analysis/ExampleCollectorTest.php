<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\ExampleCollector;
use PhpAiToolkit\Doctest\Analysis\ImportPreamble;
use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\Analysis\SourceScanner;
use PhpAiToolkit\Doctest\Analysis\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExampleCollector::class)]
#[UsesClass(SourceScanner::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(Example::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(Target::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(ImportPreamble::class)]
final class ExampleCollectorTest extends TestCase
{
    public function testCollectReturnsEveryExampleOfTheFileInDocblockOrder(): void
    {
        $examples = (new ExampleCollector())->collect(__DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php', 'src/Calculator.php');

        self::assertSame(
            [
                'Tests\Fixture\Doctest\Project\Calculator#1',
                'Tests\Fixture\Doctest\Project\Calculator::add()#1',
                'Tests\Fixture\Doctest\Project\Calculator::add()#2',
                'Tests\Fixture\Doctest\Project\Calculator::divide()#1',
                'Tests\Fixture\Doctest\Project\Calculator::printSum()#1',
                'Tests\Fixture\Doctest\Project\Calculator::shape()#1',
            ],
            array_map(static fn (Example $example): string => $example->id(), $examples),
        );
        self::assertFalse($examples[5]->runnable());
    }

    public function testLineLocatesTheExampleBodyInsideTheDocblock(): void
    {
        $docComment = "/**\n * Summary.\n *\n * @example Adding\n * add(1, 2) // => 3\n */";
        $target = new Target(Target::METHOD, '/a.php', $docComment, 'add', 10, 'App', 'Calculator');

        self::assertSame(14, (new ExampleCollector())->line($target, new DocExample('Adding', 'add(1, 2) // => 3', 'tag', 0)));
    }

    public function testLineFallsBackToTheDocblockStartWhenTheBodyIsNotFound(): void
    {
        $target = new Target(Target::METHOD, '/a.php', "/**\n * Summary.\n */", 'add', 10, 'App', 'Calculator');

        self::assertSame(10, (new ExampleCollector())->line($target, new DocExample(null, 'missing()', 'tag', 0)));
        self::assertSame(10, (new ExampleCollector())->line($target, new DocExample(null, '', 'tag', 0)));
    }

    public function testFirstCodeLineSkipsLeadingBlankLines(): void
    {
        self::assertSame('add(1, 2)', (new ExampleCollector())->firstCodeLine("\n  \n  add(1, 2)\nmore();"));
        self::assertSame('', (new ExampleCollector())->firstCodeLine("\n  \n"));
    }

    public function testStripFrameRemovesTheDocblockAsteriskFrame(): void
    {
        $collector = new ExampleCollector();

        self::assertSame('add(1, 2)', $collector->stripFrame(' *     add(1, 2)'));
        self::assertSame('Summary.', $collector->stripFrame('/** Summary.'));
        self::assertSame('bare', $collector->stripFrame('bare'));
    }
}
