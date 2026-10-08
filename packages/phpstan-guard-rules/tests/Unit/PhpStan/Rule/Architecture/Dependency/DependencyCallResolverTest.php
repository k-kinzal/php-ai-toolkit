<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantStringType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver;

/**
 * @covers \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver
 * @medium
 */
#[CoversClass(DependencyCallResolver::class)]
#[Medium]
final class DependencyCallResolverTest extends PHPStanTestCase
{
    public function testClassNameResolvesAConstantClassString(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantStringType('App\Demo'));

        self::assertSame('App\Demo', (new DependencyCallResolver(self::createReflectionProvider()))->className(new Node\Expr\Variable('class'), $scope));
    }

    public function testResolveLeavesUnknownAndDynamicCallsUnresolved(): void
    {
        $calls = new DependencyCallResolver(self::createReflectionProvider());

        self::assertNull($calls->resolve(new Node\Expr\FuncCall(new Node\Expr\Variable('callable')), self::createStub(Scope::class)));
        self::assertNull($calls->resolve(new Node\Expr\FuncCall(new Node\Name\FullyQualified('unknown_dependency_function')), self::createStub(Scope::class)));
    }

    public function testNameKeepsFullyQualifiedFunctionIdentity(): void
    {
        $reflections = self::createReflectionProvider();

        self::assertSame('file_get_contents', (new DependencyCallResolver($reflections))->name($reflections->getFunction(new Node\Name\FullyQualified('file_get_contents'), null)));
    }
    public function testArgumentRespectsNamedArgumentsAndDoesNotGuessAfterUnpacking(): void
    {
        $resolver = new DependencyCallResolver(self::createReflectionProvider());
        $path = new Node\Scalar\String_('/project/examples/input.json');
        $call = new Node\Expr\FuncCall(new Node\Name('fopen'), [
            new Node\Arg(new Node\Scalar\String_('rb'), false, false, [], new Node\Identifier('mode')),
            new Node\Arg($path, false, false, [], new Node\Identifier('filename')),
        ]);
        $unpacked = new Node\Expr\FuncCall(new Node\Name('fopen'), [new Node\Arg(new Node\Expr\Variable('args'), false, true)]);

        self::assertSame($path, $resolver->argument($call, 0, 'filename'));
        self::assertNull($resolver->argument($unpacked, 0, 'filename'));
        self::assertNull($resolver->argument($call, 2, 'absent'));
    }

}
