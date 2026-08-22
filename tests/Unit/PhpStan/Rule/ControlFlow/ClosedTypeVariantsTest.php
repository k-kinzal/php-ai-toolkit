<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use function array_map;

use PhpAiToolkit\PhpStan\Rule\ControlFlow\ClosedTypeVariants;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\BooleanType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

#[CoversClass(ClosedTypeVariants::class)]
#[Medium]
final class ClosedTypeVariantsTest extends PHPStanTestCase
{
    public function testValuesListsTheCasesOfAnEnum(): void
    {
        self::createReflectionProvider();
        $suit = 'Tests\Fixture\RequireExhaustiveDispatch\Suit';

        $variants = (new ClosedTypeVariants())->values(new ObjectType($suit));

        self::assertSame(
            [
                $suit . '::Hearts',
                $suit . '::Diamonds',
                $suit . '::Spades',
                $suit . '::Clubs',
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
}
