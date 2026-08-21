<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\ClassAncestorCollector;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\ClassNameDispatchCollector;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\ControlFlow\SubtypeIndex;
use PhpAiToolkit\PhpStan\Rule\RequireExhaustiveClassDispatchRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<RequireExhaustiveClassDispatchRule>
 */
#[CoversClass(RequireExhaustiveClassDispatchRule::class)]
#[UsesClass(ClassAncestorCollector::class)]
#[UsesClass(ClassNameDispatchCollector::class)]
#[UsesClass(DispatchSubjectResolver::class)]
#[UsesClass(RequireExhaustiveDispatchErrorBuilder::class)]
#[UsesClass(SubtypeIndex::class)]
#[Medium]
final class RequireExhaustiveClassDispatchRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new RequireExhaustiveClassDispatchRule();
    }

    #[Override]
    protected function getCollectors(): array
    {
        return [new ClassAncestorCollector(self::createReflectionProvider()), new ClassNameDispatchCollector()];
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PHPStan\Node\CollectedDataNode::class, $this->getRule()->getNodeType());
    }

    public function testErrorNamesTheClassesADispatchLeftOut(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Payment']],
            ['name' => 'App\\Transfer', 'instantiable' => true, 'ancestors' => ['App\\Transfer', 'App\\Payment']],
        ]);
        $error = (new RequireExhaustiveClassDispatchRule())->error(
            __FILE__,
            [
                'roots' => ['App\\Payment'],
                'named' => ['App\\Visa'],
                'catchAll' => true,
                'line' => 4,
                'construct' => ClassNameDispatchCollector::MATCH_CONSTRUCT,
            ],
            $index,
        );

        self::assertSame(
            'Match expression sends App\\Transfer to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
            $error === null ? null : $error->getMessage(),
        );
    }

    public function testErrorWordsASwitchWithoutADefaultCaseAsAFallThrough(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Payment']],
            ['name' => 'App\\Transfer', 'instantiable' => true, 'ancestors' => ['App\\Transfer', 'App\\Payment']],
        ]);
        $error = (new RequireExhaustiveClassDispatchRule())->error(
            __FILE__,
            [
                'roots' => ['App\\Payment'],
                'named' => ['App\\Visa'],
                'catchAll' => false,
                'line' => 4,
                'construct' => ClassNameDispatchCollector::SWITCH_CONSTRUCT,
            ],
            $index,
        );

        self::assertSame(
            RequireExhaustiveDispatchErrorBuilder::UNHANDLED_IDENTIFIER,
            $error === null ? null : $error->getIdentifier(),
        );
    }

    public function testErrorWordsASwitchWithADefaultCaseAsAbsorbing(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Payment']],
            ['name' => 'App\\Transfer', 'instantiable' => true, 'ancestors' => ['App\\Transfer', 'App\\Payment']],
        ]);
        $error = (new RequireExhaustiveClassDispatchRule())->error(
            __FILE__,
            [
                'roots' => ['App\\Payment'],
                'named' => ['App\\Visa'],
                'catchAll' => true,
                'line' => 4,
                'construct' => ClassNameDispatchCollector::SWITCH_CONSTRUCT,
            ],
            $index,
        );

        self::assertSame(
            RequireExhaustiveDispatchErrorBuilder::CATCH_ALL_IDENTIFIER,
            $error === null ? null : $error->getIdentifier(),
        );
    }

    public function testErrorAnswersWithNothingWhenEveryClassIsNamed(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Payment']],
        ]);

        self::assertNull((new RequireExhaustiveClassDispatchRule())->error(
            __FILE__,
            [
                'roots' => ['App\\Payment'],
                'named' => ['App\\Visa'],
                'catchAll' => true,
                'line' => 4,
                'construct' => ClassNameDispatchCollector::MATCH_CONSTRUCT,
            ],
            $index,
        ));
    }

    public function testErrorAnswersWithNothingWhenNoClassOfTheHierarchyIsNamed(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Payment']],
            ['name' => 'App\\Transfer', 'instantiable' => true, 'ancestors' => ['App\\Transfer', 'App\\Payment']],
        ]);

        self::assertNull((new RequireExhaustiveClassDispatchRule())->error(
            __FILE__,
            [
                'roots' => ['App\\Payment'],
                'named' => ['App\\Elsewhere'],
                'catchAll' => true,
                'line' => 4,
                'construct' => ClassNameDispatchCollector::MATCH_CONSTRUCT,
            ],
            $index,
        ));
    }

    public function testProcessNodeReportsTheClassesADispatchLeavesOut(): void
    {
        $this->analyse([
            __DIR__ . '/../../../Fixture/RequireExhaustiveDispatch/Hierarchy.php',
            __DIR__ . '/../../../Fixture/RequireExhaustiveDispatch/HierarchyDispatch.php',
        ], [
            [
                'Match expression sends Tests\\Fixture\\RequireExhaustiveDispatch\\BankTransfer, Tests\\Fixture\\RequireExhaustiveDispatch\\Wallet to its "default" arm. Write an arm for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                11,
            ],
            [
                'Switch statement does not handle Tests\\Fixture\\RequireExhaustiveDispatch\\BankTransfer, Tests\\Fixture\\RequireExhaustiveDispatch\\Wallet. Write a "case" for each of those values: the subject holds a closed set of values and this switch has no "default", so those fall through it unhandled.',
                31,
            ],
            [
                'Switch statement sends Tests\\Fixture\\RequireExhaustiveDispatch\\MasterCard to its "default" case. Write a "case" for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                43,
            ],
        ]);
    }

    public function testProcessNodeReadsAClassNameDispatchOverAUnionOfFinalClasses(): void
    {
        $this->analyse([
            __DIR__ . '/../../../Fixture/RequireExhaustiveDispatch/Shapes.php',
            __DIR__ . '/../../../Fixture/RequireExhaustiveDispatch/ShapeDispatch.php',
        ], [
            [
                'Switch statement sends Tests\\Fixture\\RequireExhaustiveDispatch\\Square, Tests\\Fixture\\RequireExhaustiveDispatch\\Triangle to its "default" case. Write a "case" for each of those values so that a value added to the closed type is reported here instead of silently taking "default".',
                63,
            ],
        ]);
    }
}
