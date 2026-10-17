<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\UnionType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Visibility\TypeNameReader;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\TypeNameReader
 */
#[CoversClass(TypeNameReader::class)]
final class TypeNameReaderTest extends TestCase
{
    public function testNamesInUnwrapsCompositeTypesAndSkipsRelativeNames(): void
    {
        $type = new UnionType([
            new Name('App\Order'),
            new IntersectionType([
                new Name('App\Readable'),
                new Name('App\Writable'),
            ]),
        ]);

        self::assertSame(
            ['App\Order', 'App\Readable', 'App\Writable'],
            array_map(static fn (Name $name): string => $name->toString(), (new TypeNameReader())->namesIn($type)),
        );
        self::assertSame([], (new TypeNameReader())->namesIn(new Name('self')));
    }

    public function testIsRelativeRecognizesParentKeyword(): void
    {
        self::assertTrue((new TypeNameReader())->isRelative(new Name('parent')));
    }
}
