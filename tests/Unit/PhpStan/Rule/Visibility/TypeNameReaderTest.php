<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

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
        $type = new \PhpParser\Node\UnionType([
            new \PhpParser\Node\Name('App\Order'),
            new \PhpParser\Node\IntersectionType([
                new \PhpParser\Node\Name('App\Readable'),
                new \PhpParser\Node\Name('App\Writable'),
            ]),
        ]);

        self::assertSame(
            ['App\Order', 'App\Readable', 'App\Writable'],
            array_map(static fn (\PhpParser\Node\Name $name): string => $name->toString(), (new TypeNameReader())->namesIn($type)),
        );
        self::assertSame([], (new TypeNameReader())->namesIn(new \PhpParser\Node\Name('self')));
    }

    public function testIsRelativeRecognizesParentKeyword(): void
    {
        self::assertTrue((new TypeNameReader())->isRelative(new \PhpParser\Node\Name('parent')));
    }
}
