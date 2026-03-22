<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PhpStanAiRules\Rule\NoControlFlowInTestMethodRule;
use PhpStanAiRules\Support\TestClassScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<NoControlFlowInTestMethodRule>
 */
#[CoversClass(NoControlFlowInTestMethodRule::class)]
#[Medium]
final class NoControlFlowInTestMethodRuleTest extends RuleTestCase
{
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
        $this->analyse([__DIR__ . '/../../Fixture/NoControlFlowInTestMethod/WithControlFlow.php'], [
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
        $this->analyse([__DIR__ . '/../../Fixture/NoControlFlowInTestMethod/WithNestedScope.php'], []);
    }

    public function testProcessNodeNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/NoControlFlowInTestMethod/NonTestClass.php'], []);
    }
}
