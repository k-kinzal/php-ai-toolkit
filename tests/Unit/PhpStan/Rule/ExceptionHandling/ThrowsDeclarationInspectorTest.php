<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ExceptionHandling;

use PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowsDeclarationInspector;
use PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowSite;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowsDeclarationInspector
 * @uses \PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowSite
 */
#[CoversClass(ThrowsDeclarationInspector::class)]
#[UsesClass(ThrowSite::class)]
#[Medium]
final class ThrowsDeclarationInspectorTest extends PHPStanTestCase
{
    public function testUncoveredClassNamesReportsUndeclaredThrow(): void
    {
        self::createReflectionProvider();
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturnCallback(static fn (Name $name): string => $name->toString());

        $uncovered = (new ThrowsDeclarationInspector())->uncoveredClassNames(
            new ThrowSite([new Name('RuntimeException')], [], 10),
            $scope,
            null,
        );

        self::assertSame(['RuntimeException'], $uncovered);
    }

    public function testUncoveredClassNamesSkipsThrowCaughtBySupertype(): void
    {
        self::createReflectionProvider();
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturnCallback(static fn (Name $name): string => $name->toString());

        $uncovered = (new ThrowsDeclarationInspector())->uncoveredClassNames(
            new ThrowSite([new Name('RuntimeException')], [new Name('Exception')], 10),
            $scope,
            null,
        );

        self::assertSame([], $uncovered);
    }

    public function testUncoveredClassNamesSkipsDeclaredThrow(): void
    {
        self::createReflectionProvider();
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturnCallback(static fn (Name $name): string => $name->toString());

        $uncovered = (new ThrowsDeclarationInspector())->uncoveredClassNames(
            new ThrowSite([new Name('RuntimeException')], [], 10),
            $scope,
            new ObjectType('RuntimeException'),
        );

        self::assertSame([], $uncovered);
    }

    public function testUncoveredClassNamesReportsThrowOutsideDeclaredType(): void
    {
        self::createReflectionProvider();
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturnCallback(static fn (Name $name): string => $name->toString());

        $uncovered = (new ThrowsDeclarationInspector())->uncoveredClassNames(
            new ThrowSite([new Name('RuntimeException')], [new Name('LogicException')], 10),
            $scope,
            new ObjectType('LogicException'),
        );

        self::assertSame(['RuntimeException'], $uncovered);
    }
}
