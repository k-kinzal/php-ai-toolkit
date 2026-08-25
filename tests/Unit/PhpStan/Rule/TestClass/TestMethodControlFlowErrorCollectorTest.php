<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpAiToolkit\PhpStan\Rule\TestClass\ControlFlowTypeResolver;
use PhpAiToolkit\PhpStan\Rule\TestClass\NestedScopeFilter;
use PhpAiToolkit\PhpStan\Rule\TestClass\TestMethodControlFlowErrorCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestClass\TestMethodControlFlowErrorCollector
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\ControlFlowTypeResolver
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\NestedScopeFilter
 */
#[CoversClass(TestMethodControlFlowErrorCollector::class)]
#[UsesClass(ControlFlowTypeResolver::class)]
#[UsesClass(NestedScopeFilter::class)]
final class TestMethodControlFlowErrorCollectorTest extends TestCase
{
    public function testErrorsReturnsControlFlowErrors(): void
    {
        $if = new \PhpParser\Node\Stmt\If_(new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('true')));

        self::assertCount(1, (new TestMethodControlFlowErrorCollector())->errors([$if], 'testExample'));
    }
}
