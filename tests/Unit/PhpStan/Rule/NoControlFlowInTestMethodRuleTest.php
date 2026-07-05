<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\ControlFlowTypeResolver;
use PhpAiToolkit\PhpStan\Rule\NestedScopeFilter;
use PhpAiToolkit\PhpStan\Rule\NoControlFlowInTestMethodRule;
use PhpAiToolkit\PhpStan\Rule\TestMethodControlFlowErrorCollector;
use PhpAiToolkit\PhpStan\Rule\TestMethodDetector;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<NoControlFlowInTestMethodRule>
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
                'Control flow statement "if" is prohibited in test method testWithIf(). Complex control flow in tests indicates the test is doing too much. Split into separate test methods or use data providers for parameterized cases. try-catch is allowed when testing exception behavior.',
                14,
            ],
            [
                'Control flow statement "foreach" is prohibited in test method testWithForeach(). Complex control flow in tests indicates the test is doing too much. Split into separate test methods or use data providers for parameterized cases. try-catch is allowed when testing exception behavior.',
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
