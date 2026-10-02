<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\OverrideAttributeDetector;
use Toolkit\PhpStan\Rule\TestClass\OverrideMethodDetector;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\OverrideMethodDetector
 * @uses \Toolkit\PhpStan\Rule\Shared\OverrideAttributeDetector
 */
#[CoversClass(OverrideMethodDetector::class)]
#[UsesClass(OverrideAttributeDetector::class)]
final class OverrideMethodDetectorTest extends TestCase
{
    public function testIsOverrideReturnsFalseWithoutClassReflection(): void
    {
        self::assertFalse((new OverrideMethodDetector())->isOverride(new \PhpParser\Node\Stmt\ClassMethod('setUp'), self::createStub(Scope::class)));
    }
}
