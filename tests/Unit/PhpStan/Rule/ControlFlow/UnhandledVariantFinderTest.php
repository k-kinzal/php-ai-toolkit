<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use function array_map;

use PhpAiToolkit\PhpStan\Rule\ControlFlow\UnhandledVariantFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnhandledVariantFinder::class)]
final class UnhandledVariantFinderTest extends TestCase
{
    public function testFindNamesTheValuesNoBranchClaims(): void
    {
        $remaining = self::createStub(Scope::class);
        $remaining->method('filterByFalseyValue')->willReturnSelf();
        $remaining->method('getType')->willReturn(
            TypeCombinator::union(new ConstantStringType('safe'), new ConstantStringType('dry')),
        );
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturn($remaining);
        $scope->method('getType')->willReturn(TypeCombinator::union(
            new ConstantStringType('fast'),
            new ConstantStringType('safe'),
            new ConstantStringType('dry'),
        ));

        $unhandled = (new UnhandledVariantFinder())->find(
            $scope,
            new \PhpParser\Node\Expr\Variable('mode'),
            [new \PhpParser\Node\Expr\Variable('claimed')],
            [new ConstantStringType('fast'), new ConstantStringType('safe'), new ConstantStringType('dry')],
        );

        self::assertSame(
            ["'safe'", "'dry'"],
            array_map(static fn (Type $variant): string => $variant->describe(VerbosityLevel::value()), $unhandled),
        );
    }

    public function testFindReturnsNothingWhenEveryValueIsClaimed(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturnSelf();
        $scope->method('getType')->willReturn(new NeverType());

        self::assertSame([], (new UnhandledVariantFinder())->find(
            $scope,
            new \PhpParser\Node\Expr\Variable('mode'),
            [new \PhpParser\Node\Expr\Variable('claimed')],
            [new ConstantStringType('fast'), new ConstantStringType('safe')],
        ));
    }

    public function testFindReturnsNothingWhenNoValueIsClaimed(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('filterByFalseyValue')->willReturnSelf();
        $scope->method('getType')->willReturn(
            TypeCombinator::union(new ConstantStringType('fast'), new ConstantStringType('safe')),
        );

        self::assertSame([], (new UnhandledVariantFinder())->find(
            $scope,
            new \PhpParser\Node\Expr\Variable('mode'),
            [],
            [new ConstantStringType('fast'), new ConstantStringType('safe')],
        ));
    }
}
