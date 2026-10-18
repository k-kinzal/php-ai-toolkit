<?php

declare(strict_types=1);

namespace Tests\Unit\Source;

use DecisionsLint\Source\Declaration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Source\Declaration
 */
#[CoversClass(Declaration::class)]
final class DeclarationTest extends TestCase
{
    public function testVisibilityReadsTheModifiersAndDefaultsToPublic(): void
    {
        $method = new Declaration('method', 'foo', 'App', 'Analyzer', 3, 5, 9, 20, ['protected', 'static']);
        self::assertSame('protected', $method->visibility());
        self::assertSame('method', $method->kind);
        self::assertSame('foo', $method->name);
        self::assertSame('App', $method->namespace);
        self::assertSame('Analyzer', $method->owner);
        self::assertSame(3, $method->docStart);
        self::assertSame(5, $method->start);
        self::assertSame(9, $method->nameEnd);
        self::assertSame(20, $method->end);
        self::assertSame(['protected', 'static'], $method->modifiers);
        self::assertSame('public', (new Declaration('function', 'f', '', null, 0, 0, 1, 2, []))->visibility());
        self::assertSame('private', (new Declaration('property', 'p', '', 'A', 0, 0, 1, 2, ['private', 'readonly']))->visibility());
    }
}
