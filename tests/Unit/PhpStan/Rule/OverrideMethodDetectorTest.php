<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\OverrideAttributeDetector;
use PhpAiToolkit\PhpStan\Rule\OverrideMethodDetector;
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
