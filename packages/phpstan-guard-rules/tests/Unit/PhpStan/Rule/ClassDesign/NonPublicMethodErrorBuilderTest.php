<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassDesign;

use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassDesign\ClassLikeNameResolver;
use Toolkit\PhpStan\Rule\ClassDesign\NonPublicMethodErrorBuilder;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassDesign\NonPublicMethodErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\ClassDesign\ClassLikeNameResolver
 */
#[CoversClass(NonPublicMethodErrorBuilder::class)]
#[UsesClass(ClassLikeNameResolver::class)]
final class NonPublicMethodErrorBuilderTest extends TestCase
{
    public function testPrivateMethodReturnsNonPublicMethodError(): void
    {
        $error = (new NonPublicMethodErrorBuilder())->privateMethod(
            new \PhpParser\Node\Stmt\ClassMethod('helper', ['flags' => Class_::MODIFIER_PRIVATE]),
            new Class_('Example'),
            self::createStub(Scope::class),
        );

        self::assertSame('customRules.nonPublicMethod', $error->getIdentifier());
    }

    public function testProtectedMethodReturnsNonPublicMethodError(): void
    {
        $error = (new NonPublicMethodErrorBuilder())->protectedMethod(
            new \PhpParser\Node\Stmt\ClassMethod('helper', ['flags' => Class_::MODIFIER_PROTECTED]),
            new Class_('Example'),
            self::createStub(Scope::class),
        );

        self::assertSame('customRules.nonPublicMethod', $error->getIdentifier());
    }
}
