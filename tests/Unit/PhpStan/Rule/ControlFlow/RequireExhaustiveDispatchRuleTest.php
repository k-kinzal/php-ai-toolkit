<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use Override;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\ClosedTypeVariants;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\DispatchInspector;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchRule;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\UnhandledVariantFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<RequireExhaustiveDispatchRule>
 * @covers \PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchRule
 * @uses \PhpAiToolkit\PhpStan\Rule\ControlFlow\ClosedTypeVariants
 * @uses \PhpAiToolkit\PhpStan\Rule\ControlFlow\DispatchInspector
 * @uses \PhpAiToolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver
 * @uses \PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\ControlFlow\UnhandledVariantFinder
 */
#[CoversClass(RequireExhaustiveDispatchRule::class)]
#[UsesClass(ClosedTypeVariants::class)]
#[UsesClass(DispatchInspector::class)]
#[UsesClass(DispatchSubjectResolver::class)]
#[UsesClass(RequireExhaustiveDispatchErrorBuilder::class)]
#[UsesClass(UnhandledVariantFinder::class)]
#[Medium]
final class RequireExhaustiveDispatchRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new RequireExhaustiveDispatchRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeReportsSwitchOverEnum(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/RequireExhaustiveDispatch/EnumSwitchDispatch.php'], [
            [
                'Switch statement does not handle Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs. Write a "case" for each of those values: the subject holds a closed set of values and this switch has no "default", so those fall through it unhandled.',
                11,
            ],
            [
                'Switch statement sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Diamonds, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" case. Write a "case" for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                23,
            ],
            [
                'Switch statement sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Diamonds, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" case. Write a "case" for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                70,
            ],
        ]);
    }

    public function testProcessNodeReportsMatchOverEnum(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/RequireExhaustiveDispatch/EnumMatchDispatch.php'], [
            [
                'Match expression sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                18,
            ],
            [
                'Match expression sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                46,
            ],
            [
                'Match expression sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Diamonds, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                69,
            ],
            [
                'Match expression sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Diamonds, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                77,
            ],
            [
                'Match expression sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Diamonds, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                85,
            ],
            [
                'Match expression sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Diamonds, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                95,
            ],
        ]);
    }

    public function testProcessNodeReportsDispatchOverClassUnion(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/RequireExhaustiveDispatch/ShapeDispatch.php'], [
            [
                'Match expression sends \'Tests\\\\Fixture\\\\RequireExhaustiveDispatch\\\\Triangle\' to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                54,
            ],
        ]);
    }

    public function testProcessNodeReportsDispatchOverConstantSubject(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/RequireExhaustiveDispatch/ConstantSubjectDispatch.php'], [
            [
                'Match expression sends \'safe\', \'dry\' to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                20,
            ],
            [
                'Match expression sends false to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                41,
            ],
            [
                'Match expression sends 2, 3 to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                52,
            ],
            [
                'Match expression sends \'diamonds\', \'spades\', \'clubs\' to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                60,
            ],
        ]);
    }

    public function testProcessNodeIgnoresDispatchOverOpenSubject(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/RequireExhaustiveDispatch/OpenSubjectDispatch.php'], []);
    }
}
