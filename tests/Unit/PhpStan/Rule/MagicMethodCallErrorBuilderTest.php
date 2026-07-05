<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\MagicMethodCallErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\MagicMethodRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MagicMethodCallErrorBuilder::class)]
#[UsesClass(MagicMethodRegistry::class)]
final class MagicMethodCallErrorBuilderTest extends TestCase
{
    public function testErrorBuildsForbiddenMagicMethodCallError(): void
    {
        self::assertSame('customRules.forbiddenMagicMethodCall', (new MagicMethodCallErrorBuilder())->error('__toString', 5)->getIdentifier());
    }
}
