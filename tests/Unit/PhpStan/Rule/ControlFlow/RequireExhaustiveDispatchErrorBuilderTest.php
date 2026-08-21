<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\Shared\LineOrderedErrors;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NullType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequireExhaustiveDispatchErrorBuilder::class)]
#[UsesClass(LineOrderedErrors::class)]
final class RequireExhaustiveDispatchErrorBuilderTest extends TestCase
{
    public function testBuildMatchCatchAllNamesTheArmAndTheValues(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildMatchCatchAll([new ConstantStringType('safe')], 12);

        self::assertSame(
            'Match expression sends \'safe\' to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
            $error->getMessage(),
        );
    }

    public function testBuildMatchCatchAllCarriesTheIdentifierAndTheLine(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildMatchCatchAll([new NullType()], 12);

        self::assertSame(
            [RequireExhaustiveDispatchErrorBuilder::CATCH_ALL_IDENTIFIER, 12],
            [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
        );
    }

    public function testBuildSwitchCatchAllNamesTheCaseAndTheValues(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildSwitchCatchAll([new ConstantStringType('safe')], 3);

        self::assertSame(
            'Switch statement sends \'safe\' to its "default" case. Write a "case" for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
            $error->getMessage(),
        );
    }

    public function testBuildSwitchCatchAllCarriesTheIdentifierAndTheLine(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildSwitchCatchAll([new NullType()], 3);

        self::assertSame(
            [RequireExhaustiveDispatchErrorBuilder::CATCH_ALL_IDENTIFIER, 3],
            [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
        );
    }

    public function testBuildSwitchUnhandledNamesTheValuesThatFallThrough(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildSwitchUnhandled([new ConstantStringType('dry')], 7);

        self::assertSame(
            'Switch statement does not handle \'dry\'. Write a "case" for each of those values: the subject holds a closed set of values and this switch has no "default", so those fall through it unhandled.',
            $error->getMessage(),
        );
    }

    public function testBuildSwitchUnhandledCarriesTheIdentifierAndTheLine(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildSwitchUnhandled([new NullType()], 7);

        self::assertSame(
            [RequireExhaustiveDispatchErrorBuilder::UNHANDLED_IDENTIFIER, 7],
            [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
        );
    }

    public function testDescribeJoinsEveryValue(): void
    {
        self::assertSame(
            "null, 'fast'",
            (new RequireExhaustiveDispatchErrorBuilder())->describe([new NullType(), new ConstantStringType('fast')]),
        );
    }
}
