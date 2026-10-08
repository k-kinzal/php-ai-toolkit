<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StringType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPath;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPathExpression;
use Toolkit\PhpStan\Rule\Architecture\Dependency\FileDependencyResolver;

/**
 * @covers \Toolkit\PhpStan\Rule\Architecture\Dependency\FileDependencyResolver
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPath
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPathExpression
 */
#[CoversClass(FileDependencyResolver::class)]
#[UsesClass(DependencyCallResolver::class)]
#[UsesClass(DependencyPath::class)]
#[UsesClass(DependencyPathExpression::class)]
final class FileDependencyResolverTest extends TestCase
{
    public function testResolveRecognizesIncludePathsWithoutExecutingTheIncludedFile(): void
    {
        $resolver = new FileDependencyResolver(new DependencyCallResolver(self::createStub(ReflectionProvider::class)), new DependencyPath('/project'));
        $node = new Node\Expr\Include_(new Node\Scalar\String_('/project/examples/danger.php'), Node\Expr\Include_::TYPE_REQUIRE);

        self::assertSame(['/project/examples/danger.php' => 'include/require'], $resolver->resolve($node, self::createStub(Scope::class)));
    }

    public function testTargetAcceptsOnlyDefiniteLocalFiles(): void
    {
        $resolver = new FileDependencyResolver(new DependencyCallResolver(self::createStub(ReflectionProvider::class)), new DependencyPath('/project'));
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantStringType('/project/examples/input.json'));
        $unknown = self::createStub(Scope::class);
        $unknown->method('getType')->willReturn(new StringType());

        self::assertSame(['/project/examples/input.json' => 'read()'], $resolver->target(new Node\Expr\Variable('path'), $scope, 'read()'));
        self::assertSame([], $resolver->target(new Node\Expr\Variable('path'), $unknown, 'read()'));
    }
}
