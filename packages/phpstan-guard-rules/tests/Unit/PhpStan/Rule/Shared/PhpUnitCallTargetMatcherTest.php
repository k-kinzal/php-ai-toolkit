<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher
 */
#[CoversClass(PhpUnitCallTargetMatcher::class)]
final class PhpUnitCallTargetMatcherTest extends TestCase
{
    public function testIsThisMethodCallReturnsTrueForThisCall(): void
    {
        $call = new MethodCall(new Variable('this'), 'assertSame');

        self::assertTrue((new PhpUnitCallTargetMatcher())->isThisMethodCall($call));
    }

    public function testIsStaticCallOnPhpUnitAssertReturnsTrueForSelfCall(): void
    {
        $call = new StaticCall(new Name('self'), 'assertSame');

        self::assertTrue((new PhpUnitCallTargetMatcher())->isStaticCallOnPhpUnitAssert($call, self::createStub(Scope::class)));
    }

    public function testIsStaticCallOnCurrentTestClassReturnsFalseForOtherClass(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\\Other');
        $call = new StaticCall(new Name('Other'), 'createMock');

        self::assertFalse((new PhpUnitCallTargetMatcher())->isStaticCallOnCurrentTestClass($call, $scope));
    }
}
