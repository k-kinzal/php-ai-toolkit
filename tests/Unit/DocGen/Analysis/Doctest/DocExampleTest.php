<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Doctest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Doctest\DocExample;

/**
 * @covers \Toolkit\DocGen\Analysis\Doctest\DocExample
 */
#[CoversClass(DocExample::class)]
final class DocExampleTest extends TestCase
{
    public function testStoresExampleData(): void
    {
        $example = new DocExample('Adding numbers', '$sum // => 3', 'tag', 2);

        self::assertSame('Adding numbers', $example->description);
        self::assertSame('$sum // => 3', $example->code);
        self::assertSame('tag', $example->source);
        self::assertSame(2, $example->index);
    }

    public function testStoresAbsentDescriptionAsNull(): void
    {
        $example = new DocExample(null, 'render();', 'fence', 0);

        self::assertNull($example->description);
        self::assertSame('render();', $example->code);
        self::assertSame('fence', $example->source);
        self::assertSame(0, $example->index);
    }
}
