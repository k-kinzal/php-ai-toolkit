<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\Shared\LineOrderedErrors;
use PHPStan\Rules\FileRuleError;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NullType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\LineOrderedErrors
 */
#[CoversClass(RequireExhaustiveDispatchErrorBuilder::class)]
#[UsesClass(LineOrderedErrors::class)]
final class RequireExhaustiveDispatchErrorBuilderTest extends TestCase
{
    public function testBuildMatchCatchAllNamesTheArmAndTheValues(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildMatchCatchAll(["'safe'"], null, 12);

        self::assertSame(
            'Match expression sends \'safe\' to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
            $error->getMessage(),
        );
    }

    public function testBuildMatchCatchAllCarriesTheIdentifierAndTheLine(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildMatchCatchAll(['null'], null, 12);

        self::assertSame(
            [RequireExhaustiveDispatchErrorBuilder::CATCH_ALL_IDENTIFIER, 12],
            [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
        );
    }

    public function testBuildSwitchCatchAllNamesTheCaseAndTheValues(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildSwitchCatchAll(["'safe'"], null, 3);

        self::assertSame(
            'Switch statement sends \'safe\' to its "default" case. Write a "case" for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
            $error->getMessage(),
        );
    }

    public function testBuildSwitchCatchAllCarriesTheIdentifierAndTheLine(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildSwitchCatchAll(['null'], null, 3);

        self::assertSame(
            [RequireExhaustiveDispatchErrorBuilder::CATCH_ALL_IDENTIFIER, 3],
            [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
        );
    }

    public function testBuildSwitchUnhandledNamesTheValuesThatFallThrough(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildSwitchUnhandled(["'dry'"], null, 7);

        self::assertSame(
            'Switch statement does not handle \'dry\'. Write a "case" for each of those values: the subject holds a closed set of values and this switch has no "default", so those fall through it unhandled.',
            $error->getMessage(),
        );
    }

    public function testBuildSwitchUnhandledCarriesTheIdentifierAndTheLine(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->buildSwitchUnhandled(['null'], null, 7);

        self::assertSame(
            [RequireExhaustiveDispatchErrorBuilder::UNHANDLED_IDENTIFIER, 7],
            [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
        );
    }

    public function testBuildLeavesTheFileToPhpStanWhenNoneIsNamed(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->build('Anything.', 'customRules.example', null, 4);

        self::assertNotInstanceOf(FileRuleError::class, $error);
    }

    public function testBuildCarriesTheNamedFile(): void
    {
        $error = (new RequireExhaustiveDispatchErrorBuilder())->build('Anything.', 'customRules.example', __FILE__, 4);

        self::assertSame(__FILE__, $error instanceof FileRuleError ? $error->getFile() : null);
    }

    public function testLabelsWritesEveryValueTheWayItIsRead(): void
    {
        self::assertSame(
            ['null', "'fast'"],
            (new RequireExhaustiveDispatchErrorBuilder())->labels([new NullType(), new ConstantStringType('fast')]),
        );
    }

    public function testDescribeJoinsEveryValue(): void
    {
        self::assertSame(
            "null, 'fast'",
            (new RequireExhaustiveDispatchErrorBuilder())->describe(['null', "'fast'"]),
        );
    }
}
