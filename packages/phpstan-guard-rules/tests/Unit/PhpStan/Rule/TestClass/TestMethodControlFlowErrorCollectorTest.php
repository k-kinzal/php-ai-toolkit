<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\If_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestClass\ControlFlowTypeResolver;
use Toolkit\PhpStan\Rule\TestClass\NestedScopeFilter;
use Toolkit\PhpStan\Rule\TestClass\TestMethodControlFlowErrorCollector;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\TestMethodControlFlowErrorCollector
 * @uses \Toolkit\PhpStan\Rule\TestClass\ControlFlowTypeResolver
 * @uses \Toolkit\PhpStan\Rule\TestClass\NestedScopeFilter
 */
#[CoversClass(TestMethodControlFlowErrorCollector::class)]
#[UsesClass(ControlFlowTypeResolver::class)]
#[UsesClass(NestedScopeFilter::class)]
final class TestMethodControlFlowErrorCollectorTest extends TestCase
{
    public function testErrorsReturnsControlFlowErrors(): void
    {
        $if = new If_(new ConstFetch(new Name('true')));

        self::assertCount(1, (new TestMethodControlFlowErrorCollector())->errors([$if], 'testExample'));
    }
}
