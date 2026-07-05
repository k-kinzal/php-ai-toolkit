<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\MagicMethodCallErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\MagicMethodCallInspector;
use PhpAiToolkit\PhpStan\Rule\MagicMethodRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
