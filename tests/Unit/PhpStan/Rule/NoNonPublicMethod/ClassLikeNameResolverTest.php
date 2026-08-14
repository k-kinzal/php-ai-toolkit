<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NoNonPublicMethod;

use PhpAiToolkit\PhpStan\Rule\NoNonPublicMethod\ClassLikeNameResolver;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassLikeNameResolver::class)]
final class ClassLikeNameResolverTest extends TestCase
{
    public function testResolveReturnsNamespaceQualifiedName(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getNamespace')->willReturn('App\\Domain');

        self::assertSame('App\\Domain\\User', (new ClassLikeNameResolver())->resolve(new \PhpParser\Node\Stmt\Class_('User'), $scope));
    }
}
