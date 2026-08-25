<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassDesign;

use PHPStan\Reflection\MethodReflection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassDesign\OverridableMethodPolicy;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassDesign\OverridableMethodPolicy
 */
#[CoversClass(OverridableMethodPolicy::class)]
final class OverridableMethodPolicyTest extends TestCase
{
    public function testAllowsReturnsTrueForVisibleParentMethod(): void
    {
        $parentMethod = self::createStub(MethodReflection::class);
        $parentMethod->method('isPrivate')->willReturn(false);

        self::assertTrue((new OverridableMethodPolicy())->allows('run', $parentMethod));
    }

    public function testAllowsReturnsFalseForConstructor(): void
    {
        $parentMethod = self::createStub(MethodReflection::class);
        $parentMethod->method('isPrivate')->willReturn(false);

        self::assertFalse((new OverridableMethodPolicy())->allows('__construct', $parentMethod));
    }

    public function testAllowsReturnsFalseForConstructorSpelledInMixedCase(): void
    {
        $parentMethod = self::createStub(MethodReflection::class);
        $parentMethod->method('isPrivate')->willReturn(false);

        self::assertFalse((new OverridableMethodPolicy())->allows('__CONSTRUCT', $parentMethod));
    }

    public function testAllowsReturnsFalseForPrivateParentMethod(): void
    {
        $parentMethod = self::createStub(MethodReflection::class);
        $parentMethod->method('isPrivate')->willReturn(true);

        self::assertFalse((new OverridableMethodPolicy())->allows('hidden', $parentMethod));
    }
}
