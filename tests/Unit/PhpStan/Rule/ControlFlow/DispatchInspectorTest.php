<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use function array_map;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\ControlFlow\ClosedTypeVariants;
use Toolkit\PhpStan\Rule\ControlFlow\DispatchInspector;
use Toolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver;
use Toolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchErrorBuilder;
use Toolkit\PhpStan\Rule\ControlFlow\UnhandledVariantFinder;
use Toolkit\PhpStan\Rule\Shared\LineOrderedErrors;

/**
 * @covers \Toolkit\PhpStan\Rule\ControlFlow\DispatchInspector
 * @uses \Toolkit\PhpStan\Rule\ControlFlow\ClosedTypeVariants
 * @uses \Toolkit\PhpStan\Rule\Shared\LineOrderedErrors
 * @uses \Toolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver
 * @uses \Toolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\ControlFlow\UnhandledVariantFinder
 * @medium
 */
#[CoversClass(DispatchInspector::class)]
#[UsesClass(ClosedTypeVariants::class)]
#[UsesClass(LineOrderedErrors::class)]
#[UsesClass(DispatchSubjectResolver::class)]
#[UsesClass(RequireExhaustiveDispatchErrorBuilder::class)]
#[UsesClass(UnhandledVariantFinder::class)]
#[Medium]
final class DispatchInspectorTest extends PHPStanTestCase
{
    public function testSwitchErrorsReportsTheValuesTheDefaultCaseAbsorbs(): void
    {
        $remaining = self::createStub(Scope::class);
        $remaining->method('filterByFalseyValue')->willReturnSelf();
        $remaining->method('getType')->willReturn(new ConstantStringType('dry'));
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturn($remaining);
        $scope->method('getType')->willReturn(TypeCombinator::union(
            new ConstantStringType('fast'),
            new ConstantStringType('dry'),
        ));
        $switch = new \PhpParser\Node\Stmt\Switch_(
            new \PhpParser\Node\Expr\Variable('mode'),
            [
                new \PhpParser\Node\Stmt\Case_(new \PhpParser\Node\Scalar\String_('fast')),
                new \PhpParser\Node\Stmt\Case_(null),
            ],
            ['startLine' => 5],
        );

        self::assertSame(
            [[RequireExhaustiveDispatchErrorBuilder::CATCH_ALL_IDENTIFIER, 5]],
            array_map(
                static fn (IdentifierRuleError $error): array => [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
                (new DispatchInspector())->switchErrors($switch, $scope),
            ),
        );
    }

    public function testSwitchErrorsReportsTheValuesThatFallThroughWithoutADefaultCase(): void
    {
        $remaining = self::createStub(Scope::class);
        $remaining->method('filterByFalseyValue')->willReturnSelf();
        $remaining->method('getType')->willReturn(new ConstantStringType('dry'));
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturn($remaining);
        $scope->method('getType')->willReturn(TypeCombinator::union(
            new ConstantStringType('fast'),
            new ConstantStringType('dry'),
        ));
        $switch = new \PhpParser\Node\Stmt\Switch_(
            new \PhpParser\Node\Expr\Variable('mode'),
            [new \PhpParser\Node\Stmt\Case_(new \PhpParser\Node\Scalar\String_('fast'))],
            ['startLine' => 8],
        );

        self::assertSame(
            [[RequireExhaustiveDispatchErrorBuilder::UNHANDLED_IDENTIFIER, 8]],
            array_map(
                static fn (IdentifierRuleError $error): array => [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
                (new DispatchInspector())->switchErrors($switch, $scope),
            ),
        );
    }

    public function testSwitchErrorsReadsTheCasesWrittenAfterTheDefaultCase(): void
    {
        $remaining = self::createStub(Scope::class);
        $remaining->method('filterByFalseyValue')->willReturnSelf();
        $remaining->method('getType')->willReturn(new ConstantStringType('dry'));
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturn($remaining);
        $scope->method('getType')->willReturn(TypeCombinator::union(
            new ConstantStringType('fast'),
            new ConstantStringType('dry'),
        ));
        $switch = new \PhpParser\Node\Stmt\Switch_(
            new \PhpParser\Node\Expr\Variable('mode'),
            [
                new \PhpParser\Node\Stmt\Case_(null),
                new \PhpParser\Node\Stmt\Case_(new \PhpParser\Node\Scalar\String_('fast')),
            ],
            ['startLine' => 5],
        );

        self::assertSame(
            [[RequireExhaustiveDispatchErrorBuilder::CATCH_ALL_IDENTIFIER, 5]],
            array_map(
                static fn (IdentifierRuleError $error): array => [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
                (new DispatchInspector())->switchErrors($switch, $scope),
            ),
        );
    }

    public function testSwitchErrorsReturnsNothingWhenEveryValueIsClaimed(): void
    {
        $remaining = self::createStub(Scope::class);
        $remaining->method('filterByFalseyValue')->willReturnSelf();
        $remaining->method('getType')->willReturn(new NeverType());
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturn($remaining);
        $scope->method('getType')->willReturn(TypeCombinator::union(
            new ConstantStringType('fast'),
            new ConstantStringType('dry'),
        ));
        $switch = new \PhpParser\Node\Stmt\Switch_(
            new \PhpParser\Node\Expr\Variable('mode'),
            [
                new \PhpParser\Node\Stmt\Case_(new \PhpParser\Node\Scalar\String_('fast')),
                new \PhpParser\Node\Stmt\Case_(new \PhpParser\Node\Scalar\String_('dry')),
            ],
            ['startLine' => 5],
        );

        self::assertSame([], (new DispatchInspector())->switchErrors($switch, $scope));
    }

    public function testMatchErrorsReportsTheValuesTheDefaultArmAbsorbs(): void
    {
        $remaining = self::createStub(Scope::class);
        $remaining->method('filterByFalseyValue')->willReturnSelf();
        $remaining->method('getType')->willReturn(new ConstantStringType('dry'));
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturn($remaining);
        $scope->method('getType')->willReturn(TypeCombinator::union(
            new ConstantStringType('fast'),
            new ConstantStringType('dry'),
        ));
        $node = new \PhpParser\Node\Expr\Match_(
            new \PhpParser\Node\Expr\Variable('mode'),
            [
                new \PhpParser\Node\MatchArm(
                    [new \PhpParser\Node\Scalar\String_('fast')],
                    new \PhpParser\Node\Scalar\String_('f'),
                ),
                new \PhpParser\Node\MatchArm(null, new \PhpParser\Node\Scalar\String_('x')),
            ],
            ['startLine' => 9],
        );

        self::assertSame(
            [[RequireExhaustiveDispatchErrorBuilder::CATCH_ALL_IDENTIFIER, 9]],
            array_map(
                static fn (IdentifierRuleError $error): array => [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
                (new DispatchInspector())->matchErrors($node, $scope),
            ),
        );
    }

    public function testMatchErrorsReadsTheArmsWrittenAfterTheDefaultArm(): void
    {
        $remaining = self::createStub(Scope::class);
        $remaining->method('filterByFalseyValue')->willReturnSelf();
        $remaining->method('getType')->willReturn(new ConstantStringType('dry'));
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturn($remaining);
        $scope->method('getType')->willReturn(TypeCombinator::union(
            new ConstantStringType('fast'),
            new ConstantStringType('dry'),
        ));
        $node = new \PhpParser\Node\Expr\Match_(
            new \PhpParser\Node\Expr\Variable('mode'),
            [
                new \PhpParser\Node\MatchArm(null, new \PhpParser\Node\Scalar\String_('x')),
                new \PhpParser\Node\MatchArm(
                    [new \PhpParser\Node\Scalar\String_('fast')],
                    new \PhpParser\Node\Scalar\String_('f'),
                ),
            ],
            ['startLine' => 9],
        );

        self::assertSame(
            [[RequireExhaustiveDispatchErrorBuilder::CATCH_ALL_IDENTIFIER, 9]],
            array_map(
                static fn (IdentifierRuleError $error): array => [$error->getIdentifier(), (new LineOrderedErrors())->lineOf($error)],
                (new DispatchInspector())->matchErrors($node, $scope),
            ),
        );
    }

    public function testMatchErrorsReturnsNothingWithoutADefaultArm(): void
    {
        $remaining = self::createStub(Scope::class);
        $remaining->method('filterByFalseyValue')->willReturnSelf();
        $remaining->method('getType')->willReturn(new ConstantStringType('dry'));
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturn($remaining);
        $scope->method('getType')->willReturn(TypeCombinator::union(
            new ConstantStringType('fast'),
            new ConstantStringType('dry'),
        ));
        $node = new \PhpParser\Node\Expr\Match_(
            new \PhpParser\Node\Expr\Variable('mode'),
            [
                new \PhpParser\Node\MatchArm(
                    [new \PhpParser\Node\Scalar\String_('fast')],
                    new \PhpParser\Node\Scalar\String_('f'),
                ),
            ],
            ['startLine' => 9],
        );

        self::assertSame([], (new DispatchInspector())->matchErrors($node, $scope));
    }

    public function testUnhandledReturnsNothingWhenTheSubjectIsOpen(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturnSelf();
        $scope->method('getType')->willReturn(new StringType());

        self::assertSame([], (new DispatchInspector())->unhandled(
            new \PhpParser\Node\Expr\Variable('mode'),
            [],
            $scope,
        ));
    }

    public function testUnhandledReturnsNothingWhenTheConstructNamesNoSubject(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturnSelf();
        $scope->method('getType')->willReturn(new ConstantBooleanType(true));

        self::assertSame([], (new DispatchInspector())->unhandled(
            new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('true')),
            [],
            $scope,
        ));
    }
}
