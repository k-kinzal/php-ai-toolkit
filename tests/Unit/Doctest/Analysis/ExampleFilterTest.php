<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\ExampleFilter;
use PhpAiToolkit\Doctest\Analysis\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExampleFilter::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
final class ExampleFilterTest extends TestCase
{
    public function testApplyReturnsEveryExampleWhenThereIsNoFilter(): void
    {
        $examples = [new Example(new Target(Target::METHOD, 'src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger', [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 12), new Example(new Target(Target::METHOD, 'src/Ledger.php', '/** */', 'close', 10, 'App', 'Ledger', [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 12)];

        self::assertSame($examples, (new ExampleFilter())->apply($examples, null));
        self::assertSame($examples, (new ExampleFilter())->apply($examples, ''));
    }

    public function testApplyKeepsOnlyTheMatchingExamples(): void
    {
        $append = new Example(new Target(Target::METHOD, 'src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger', [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 12);
        $matched = (new ExampleFilter())->apply([$append, new Example(new Target(Target::METHOD, 'src/Other.php', '/** */', 'close', 10, 'App', 'Ledger', [], 'src/Other.php'), new DocExample(null, 'run()', 'tag', 0), 12)], 'append');

        self::assertSame([$append], $matched);
    }

    public function testMatchesOnIdentifierAndPathCaseInsensitively(): void
    {
        $example = new Example(new Target(Target::METHOD, 'src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger', [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 12);
        $filter = new ExampleFilter();

        self::assertTrue($filter->matches($example, 'App\Ledger::append()#1'));
        self::assertTrue($filter->matches($example, 'APPEND'));
        self::assertTrue($filter->matches($example, 'src/Ledger.php'));
        self::assertFalse($filter->matches($example, 'withdraw'));
    }
}
