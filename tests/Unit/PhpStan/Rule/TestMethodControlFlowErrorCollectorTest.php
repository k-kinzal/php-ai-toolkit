<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\ControlFlowTypeResolver;
use PhpAiToolkit\PhpStan\Rule\NestedScopeFilter;
use PhpAiToolkit\PhpStan\Rule\TestMethodControlFlowErrorCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
