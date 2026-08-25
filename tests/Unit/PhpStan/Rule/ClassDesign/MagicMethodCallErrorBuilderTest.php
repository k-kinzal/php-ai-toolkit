<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassDesign;

use PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodCallErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodCallErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry
 */
#[CoversClass(MagicMethodCallErrorBuilder::class)]
#[UsesClass(MagicMethodRegistry::class)]
final class MagicMethodCallErrorBuilderTest extends TestCase
{
    public function testErrorBuildsForbiddenMagicMethodCallError(): void
    {
        self::assertSame('customRules.forbiddenMagicMethodCall', (new MagicMethodCallErrorBuilder())->error('__toString', 5)->getIdentifier());
    }
}
