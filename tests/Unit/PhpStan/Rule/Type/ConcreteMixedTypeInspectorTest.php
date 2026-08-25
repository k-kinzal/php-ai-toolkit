<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PhpAiToolkit\PhpStan\Rule\Type\ConcreteMixedTypeInspector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConcreteMixedTypeInspector::class)]
final class ConcreteMixedTypeInspectorTest extends TestCase
{
    public function testContainsFindsOnlyExplicitConcreteMixed(): void
    {
        $inspector = new ConcreteMixedTypeInspector();

        self::assertTrue($inspector->contains(new MixedType(true)));
        self::assertTrue($inspector->contains(new ArrayType(new IntegerType(), new MixedType(true))));
        self::assertFalse($inspector->contains(new MixedType(false)));
        self::assertFalse($inspector->contains(new IntegerType()));
    }

    public function testDescribeNamesTheCompleteContainingType(): void
    {
        $type = new ArrayType(new IntegerType(), new MixedType(true));

        self::assertSame('array<int, mixed>', (new ConcreteMixedTypeInspector())->describe($type));
    }
}
