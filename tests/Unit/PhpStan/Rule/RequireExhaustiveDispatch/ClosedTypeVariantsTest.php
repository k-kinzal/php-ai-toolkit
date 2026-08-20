<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\RequireExhaustiveDispatch;

use function array_map;

use PhpAiToolkit\PhpStan\Rule\RequireExhaustiveDispatch\ClosedTypeVariants;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\BooleanType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

use function range;

use Tests\Fixture\RequireExhaustiveDispatch\Circle;
use Tests\Fixture\RequireExhaustiveDispatch\Square;
use Tests\Fixture\RequireExhaustiveDispatch\Suit;

#[CoversClass(ClosedTypeVariants::class)]
#[Medium]
final class ClosedTypeVariantsTest extends PHPStanTestCase
{
    public function testValuesListsTheCasesOfAnEnum(): void
    {
        self::createReflectionProvider();

        $variants = (new ClosedTypeVariants())->values(new ObjectType(Suit::class));

        self::assertSame(
            [
                Suit::class . '::Hearts',
                Suit::class . '::Diamonds',
                Suit::class . '::Spades',
                Suit::class . '::Clubs',
            ],
            array_map(static fn (Type $variant): string => $variant->describe(VerbosityLevel::value()), $variants ?? []),
        );
    }

    public function testValuesListsBothBooleans(): void
    {
        $variants = (new ClosedTypeVariants())->values(new BooleanType());

        self::assertSame(
            ['true', 'false'],
            array_map(static fn (Type $variant): string => $variant->describe(VerbosityLevel::value()), $variants ?? []),
        );
    }

    public function testValuesReturnsNullForAnUnboundedType(): void
    {
        self::assertNull((new ClosedTypeVariants())->values(new StringType()));
    }

    public function testValuesReturnsNullBeyondTheVariantLimit(): void
    {
        self::assertNull((new ClosedTypeVariants())->values(IntegerRangeType::fromInterval(0, ClosedTypeVariants::MAX_VARIANTS)));
    }

    public function testValuesStaysWithinTheVariantLimit(): void
    {
        $variants = (new ClosedTypeVariants())->values(IntegerRangeType::fromInterval(1, ClosedTypeVariants::MAX_VARIANTS));

        self::assertCount(ClosedTypeVariants::MAX_VARIANTS, $variants ?? []);
    }

    public function testObjectsListsTheClassesOfAUnionOfObjects(): void
    {
        self::createReflectionProvider();

        $variants = (new ClosedTypeVariants())->objects(
            TypeCombinator::union(new ObjectType(Circle::class), new ObjectType(Square::class)),
        );

        self::assertSame(
            [Circle::class, Square::class],
            array_map(static fn (Type $variant): string => $variant->describe(VerbosityLevel::value()), $variants ?? []),
        );
    }

    public function testObjectsReturnsNullForASingleClass(): void
    {
        self::createReflectionProvider();

        self::assertNull((new ClosedTypeVariants())->objects(new ObjectType(Circle::class)));
    }

    public function testObjectsReturnsNullForATypeWithoutClasses(): void
    {
        self::assertNull((new ClosedTypeVariants())->objects(new StringType()));
    }

    public function testObjectsReturnsNullWhenTheTypeHoldsMoreThanItsClasses(): void
    {
        self::createReflectionProvider();

        self::assertNull((new ClosedTypeVariants())->objects(
            TypeCombinator::union(new ObjectType(Circle::class), new ObjectType(Square::class), new NullType()),
        ));
    }

    public function testObjectsStaysWithinTheVariantLimit(): void
    {
        self::createReflectionProvider();

        $variants = (new ClosedTypeVariants())->objects(TypeCombinator::union(
            ...array_map(
                static fn (int $index): Type => new ObjectType('Tests\\Fixture\\RequireExhaustiveDispatch\\Absent' . $index),
                range(1, ClosedTypeVariants::MAX_VARIANTS),
            ),
        ));

        self::assertCount(ClosedTypeVariants::MAX_VARIANTS, $variants ?? []);
    }

    public function testObjectsReturnsNullBeyondTheVariantLimit(): void
    {
        self::createReflectionProvider();

        self::assertNull((new ClosedTypeVariants())->objects(TypeCombinator::union(
            ...array_map(
                static fn (int $index): Type => new ObjectType('Tests\\Fixture\\RequireExhaustiveDispatch\\Absent' . $index),
                range(1, ClosedTypeVariants::MAX_VARIANTS + 1),
            ),
        )));
    }
}
