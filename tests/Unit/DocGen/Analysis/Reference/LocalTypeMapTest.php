<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Reference;

use PhpAiToolkit\DocGen\Analysis\Reference\LocalTypeMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocalTypeMap::class)]
final class LocalTypeMapTest extends TestCase
{
    public function testSetStoresVariableTypeCaseInsensitively(): void
    {
        $map = new LocalTypeMap();
        $map->set('Greeter', 'Demo\Greeter');

        self::assertSame('Demo\Greeter', $map->typeOf('greeter'));
        self::assertSame('Demo\Greeter', $map->typeOf('GREETER'));
    }

    public function testSetOverwritesEarlierTypeOfSameVariable(): void
    {
        $map = new LocalTypeMap();
        $map->set('value', 'Demo\Alpha');
        $map->set('VALUE', 'Demo\Beta');

        self::assertSame('Demo\Beta', $map->typeOf('value'));
    }

    public function testTypeOfReturnsNullForUnknownVariable(): void
    {
        self::assertNull((new LocalTypeMap())->typeOf('missing'));
    }

    public function testForgetRemovesVariableCaseInsensitively(): void
    {
        $map = new LocalTypeMap();
        $map->set('greeter', 'Demo\Greeter');
        $map->forget('GREETER');

        self::assertNull($map->typeOf('greeter'));
    }

    public function testAllReturnsTypesKeyedByLowercasedVariableName(): void
    {
        $map = new LocalTypeMap();
        $map->set('Greeter', 'Demo\Greeter');
        $map->set('other', 'Demo\Other');

        self::assertSame(['greeter' => 'Demo\Greeter', 'other' => 'Demo\Other'], $map->all());
    }
}
