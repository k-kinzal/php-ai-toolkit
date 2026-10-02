<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\NoControlFlowInTestMethodRule;
use Toolkit\PhpStan\Rule\Shared\TestMethodDetector;
use Toolkit\PhpStan\Rule\TestClass\ControlFlowTypeResolver;
use Toolkit\PhpStan\Rule\TestClass\NestedScopeFilter;
use Toolkit\PhpStan\Rule\TestClass\TestMethodControlFlowErrorCollector;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * @extends RuleTestCase<NoControlFlowInTestMethodRule>
 * @covers \Toolkit\PhpStan\Rule\NoControlFlowInTestMethodRule
 * @uses \Toolkit\PhpStan\Rule\TestClass\ControlFlowTypeResolver
 * @uses \Toolkit\PhpStan\Rule\TestClass\NestedScopeFilter
 * @uses \Toolkit\PhpStan\Rule\TestClass\TestMethodControlFlowErrorCollector
 * @uses \Toolkit\PhpStan\Rule\Shared\TestMethodDetector
 * @medium
 */
#[CoversClass(NoControlFlowInTestMethodRule::class)]
#[UsesClass(ControlFlowTypeResolver::class)]
#[UsesClass(NestedScopeFilter::class)]
#[UsesClass(TestMethodControlFlowErrorCollector::class)]
#[UsesClass(TestMethodDetector::class)]
#[Medium]
final class NoControlFlowInTestMethodRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new NoControlFlowInTestMethodRule(new TestClassScope());
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassMethod::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeControlFlowInTestMethodIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoControlFlowInTestMethod/WithControlFlow.php'], [
            [
                'Split test method testWithIf() so it contains no "if" statement. Use separate tests or a data provider for each case.',
                14,
            ],
            [
                'Split test method testWithForeach() so it contains no "foreach" statement. Use separate tests or a data provider for each case.',
                21,
            ],
        ]);
    }

    public function testProcessNodeControlFlowInClosureIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoControlFlowInTestMethod/WithNestedScope.php'], []);
    }

    public function testProcessNodeNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoControlFlowInTestMethod/NonTestClass.php'], []);
    }
}
