<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassDesign;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry
 */
#[CoversClass(MagicMethodRegistry::class)]
final class MagicMethodRegistryTest extends TestCase
{
    public function testIsMagicDetectsMagicMethodNames(): void
    {
        self::assertTrue((new MagicMethodRegistry())->isMagic('__toString'));
        self::assertFalse((new MagicMethodRegistry())->isMagic('toString'));
    }

    public function testAlternativeReturnsMagicMethodAlternative(): void
    {
        self::assertStringContainsString('(string)', (new MagicMethodRegistry())->alternative('__toString'));
    }
}
