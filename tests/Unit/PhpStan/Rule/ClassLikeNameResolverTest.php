<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\ClassLikeNameResolver;
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
