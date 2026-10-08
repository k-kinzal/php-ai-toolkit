<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver;
use Toolkit\PhpStan\Rule\Architecture\Dependency\SymbolDependencyResolver;
use Toolkit\PhpStan\Rule\Visibility\TypeNameReader;
use Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector;

/**
 * @covers \Toolkit\PhpStan\Rule\Architecture\Dependency\SymbolDependencyResolver
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver
 * @uses \Toolkit\PhpStan\Rule\Visibility\TypeNameReader
 * @uses \Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector
 * @medium
 */
#[CoversClass(SymbolDependencyResolver::class)]
#[UsesClass(DependencyCallResolver::class)]
#[UsesClass(TypeNameReader::class)]
#[UsesClass(VisibilityReferenceCollector::class)]
#[Medium]
final class SymbolDependencyResolverTest extends PHPStanTestCase
{
    public function testResolveIgnoresUnresolvedGlobalConstants(): void
    {
        $reflections = self::createReflectionProvider();
        $symbols = new SymbolDependencyResolver($reflections, new DependencyCallResolver($reflections));

        self::assertSame([], $symbols->resolve(new Node\Expr\ConstFetch(new Node\Name\FullyQualified('UNKNOWN_DEPENDENCY_CONSTANT')), self::createStub(Scope::class)));
    }

    public function testExpressionTargetsFindsTypedPropertyReceivers(): void
    {
        $reflections = self::createReflectionProvider();
        $symbols = new SymbolDependencyResolver($reflections, new DependencyCallResolver($reflections));
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ObjectType(self::class));

        self::assertSame([__FILE__ => self::class], $symbols->expressionTargets(new Node\Expr\PropertyFetch(new Node\Expr\Variable('object'), 'value'), $scope));
    }

    public function testClassTargetUsesTheDeclarationFileInsteadOfTheNamespaceSpelling(): void
    {
        $reflections = self::createReflectionProvider();
        $symbols = new SymbolDependencyResolver($reflections, new DependencyCallResolver($reflections));

        self::assertSame([
            dirname(__DIR__, 6) . '/src/PhpStan/Rule/Architecture/Dependency/SymbolDependencyResolver.php' => SymbolDependencyResolver::class,
        ], $symbols->classTarget(SymbolDependencyResolver::class));
        self::assertSame([], $symbols->classTarget('Unknown\Example'));
    }
}
