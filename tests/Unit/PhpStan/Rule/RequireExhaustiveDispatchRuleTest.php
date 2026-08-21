<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\ClosedTypeVariants;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\DispatchInspector;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\UnhandledVariantFinder;
use PhpAiToolkit\PhpStan\Rule\RequireExhaustiveDispatchRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<RequireExhaustiveDispatchRule>
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
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExhaustiveDispatch/EnumSwitchDispatch.php'], [
            [
                'Switch statement does not handle Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs. Write a "case" for each of those values: the subject holds a closed set of values and this switch has no "default", so those fall through it unhandled.',
                11,
            ],
            [
                'Switch statement sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Diamonds, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" case. Write a "case" for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                23,
            ],
        ]);
    }

    public function testProcessNodeReportsMatchOverEnum(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExhaustiveDispatch/EnumMatchDispatch.php'], [
            [
                'Match expression sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Spades, Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                11,
            ],
            [
                'Match expression sends Tests\\Fixture\\RequireExhaustiveDispatch\\Suit::Clubs to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                39,
            ],
        ]);
    }

    public function testProcessNodeReportsDispatchOverClassUnion(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExhaustiveDispatch/ShapeDispatch.php'], [
            [
                'Match expression sends Tests\\Fixture\\RequireExhaustiveDispatch\\Triangle to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                13,
            ],
            [
                'Switch statement does not handle Tests\\Fixture\\RequireExhaustiveDispatch\\Triangle. Write a "case" for each of those values: the subject holds a closed set of values and this switch has no "default", so those fall through it unhandled.',
                32,
            ],
            [
                'Match expression sends \'Tests\\\\Fixture\\\\RequireExhaustiveDispatch\\\\Triangle\' to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                54,
            ],
        ]);
    }

    public function testProcessNodeReportsDispatchOverConstantSubject(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExhaustiveDispatch/ConstantSubjectDispatch.php'], [
            [
                'Match expression sends \'safe\', \'dry\' to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                20,
            ],
            [
                'Match expression sends false to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                41,
            ],
        ]);
    }

    public function testProcessNodeIgnoresDispatchOverOpenSubject(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExhaustiveDispatch/OpenSubjectDispatch.php'], []);
    }
}
