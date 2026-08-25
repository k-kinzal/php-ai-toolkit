<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassDesign;

use PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodCallErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodCallInspector;
use PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodCallInspector
 * @uses \PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodCallErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry
 */
#[CoversClass(MagicMethodCallInspector::class)]
#[UsesClass(MagicMethodCallErrorBuilder::class)]
#[UsesClass(MagicMethodRegistry::class)]
final class MagicMethodCallInspectorTest extends TestCase
{
    public function testErrorsReturnsMagicMethodCallError(): void
    {
        $call = new \PhpParser\Node\Expr\MethodCall(new \PhpParser\Node\Expr\Variable('object'), '__toString');

        self::assertCount(1, (new MagicMethodCallInspector())->errors($call));
    }
}
