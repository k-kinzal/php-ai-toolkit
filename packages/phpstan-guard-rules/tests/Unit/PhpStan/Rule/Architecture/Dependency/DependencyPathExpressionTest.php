<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\StringType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPathExpression;

/**
 * @covers \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPathExpression
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver
 */
#[CoversClass(DependencyPathExpression::class)]
#[UsesClass(DependencyCallResolver::class)]
final class DependencyPathExpressionTest extends TestCase
{
    public function testResolveUsesTheSourceFileForMagicConstantsAndConcatenation(): void
    {
        $paths = new DependencyPathExpression(new DependencyCallResolver(self::createStub(ReflectionProvider::class)));
        $scope = self::createStub(Scope::class);
        $scope->method('getFile')->willReturn('/project/tests/ClientTest.php');
        $scope->method('getType')->willReturn(new StringType());
        $expression = new Node\Expr\BinaryOp\Concat(new Node\Scalar\MagicConst\Dir(), new Node\Scalar\String_('/../examples/input.json'));

        self::assertSame('/project/tests/../examples/input.json', $paths->resolve($expression, $scope));
        self::assertSame('/project/tests/ClientTest.php', $paths->resolve(new Node\Scalar\MagicConst\File(), $scope));
        self::assertNull($paths->resolve(new Node\Expr\Variable('path'), $scope));
    }

    public function testDirectoryAcceptsNamedLevelsWithoutExecutingArbitraryFunctions(): void
    {
        $paths = new DependencyPathExpression(new DependencyCallResolver(self::createStub(ReflectionProvider::class)));
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantIntegerType(2));
        $call = new Node\Expr\FuncCall(new Node\Name('dirname'), [
            new Node\Arg(new Node\Scalar\LNumber(2), false, false, [], new Node\Identifier('levels')),
            new Node\Arg(new Node\Scalar\String_('/project/tests/unit'), false, false, [], new Node\Identifier('path')),
        ]);

        self::assertSame('/project', $paths->directory($call, $scope));
    }

    public function testDirectoryDoesNotAssumeTheDefaultLevelWhenArgumentsAreUnpacked(): void
    {
        $paths = new DependencyPathExpression(new DependencyCallResolver(self::createStub(ReflectionProvider::class)));
        $call = new Node\Expr\FuncCall(new Node\Name('dirname'), [
            new Node\Arg(new Node\Scalar\String_('/project/tests')),
            new Node\Arg(new Node\Expr\Variable('levels'), false, true),
        ]);

        self::assertNull($paths->directory($call, self::createStub(Scope::class)));
    }
}
