<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassDesign;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassDesign\MagicMethodCallErrorBuilder;
use Toolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassDesign\MagicMethodCallErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry
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
