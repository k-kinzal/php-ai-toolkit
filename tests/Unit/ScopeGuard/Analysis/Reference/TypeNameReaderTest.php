<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Reference;

use PhpAiToolkit\ScopeGuard\Analysis\Reference\TypeNameReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TypeNameReader::class)]
final class TypeNameReaderTest extends TestCase
{
    public function testNamesInReturnsAPlainClassName(): void
    {
        $names = (new TypeNameReader())->namesIn(new \PhpParser\Node\Name('App\\Domain\\Order'));

        self::assertSame('App\\Domain\\Order', $names[0]->toString());
    }

    public function testNamesInUnwrapsNullableType(): void
    {
        $names = (new TypeNameReader())->namesIn(new \PhpParser\Node\NullableType(new \PhpParser\Node\Name('App\\Order')));

        self::assertSame('App\\Order', $names[0]->toString());
    }

    public function testNamesInUnwrapsUnionType(): void
    {
        $union = new \PhpParser\Node\UnionType([new \PhpParser\Node\Name('App\\Order'), new \PhpParser\Node\Name('App\\Cart')]);

        self::assertCount(2, (new TypeNameReader())->namesIn($union));
    }

    public function testNamesInUnwrapsIntersectionInsideUnion(): void
    {
        $intersection = new \PhpParser\Node\IntersectionType([new \PhpParser\Node\Name('App\\Order')]);
        $union = new \PhpParser\Node\UnionType([$intersection, new \PhpParser\Node\Identifier('null')]);

        self::assertCount(1, (new TypeNameReader())->namesIn($union));
    }

    public function testNamesInIgnoresBuiltInType(): void
    {
        self::assertSame([], (new TypeNameReader())->namesIn(new \PhpParser\Node\Identifier('int')));
    }

    public function testNamesInIgnoresAbsentType(): void
    {
        self::assertSame([], (new TypeNameReader())->namesIn(null));
    }

    public function testNamesInIgnoresSelfKeyword(): void
    {
        self::assertSame([], (new TypeNameReader())->namesIn(new \PhpParser\Node\Name('self')));
    }

    public function testIsRelativeAcceptsStaticKeywordInAnyCase(): void
    {
        self::assertTrue((new TypeNameReader())->isRelative(new \PhpParser\Node\Name('STATIC')));
    }

    public function testIsRelativeAcceptsParentKeyword(): void
    {
        self::assertTrue((new TypeNameReader())->isRelative(new \PhpParser\Node\Name('parent')));
    }

    public function testIsRelativeRejectsAClassName(): void
    {
        self::assertFalse((new TypeNameReader())->isRelative(new \PhpParser\Node\Name('App\\Order')));
    }
}
