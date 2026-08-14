<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NoHelperMethodInTestClass;

use PhpAiToolkit\PhpStan\Rule\NoHelperMethodInTestClass\OverrideMethodDetector;
use PhpAiToolkit\PhpStan\Rule\Shared\OverrideAttributeDetector;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OverrideMethodDetector::class)]
#[UsesClass(OverrideAttributeDetector::class)]
final class OverrideMethodDetectorTest extends TestCase
{
    public function testIsOverrideReturnsFalseWithoutClassReflection(): void
    {
        self::assertFalse((new OverrideMethodDetector())->isOverride(new \PhpParser\Node\Stmt\ClassMethod('setUp'), self::createStub(Scope::class)));
    }
}
