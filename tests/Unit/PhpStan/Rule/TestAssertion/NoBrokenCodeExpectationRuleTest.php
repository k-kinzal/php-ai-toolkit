<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use Override;
use PhpAiToolkit\PhpStan\Rule\Shared\CallArgumentResolver;
use PhpAiToolkit\PhpStan\Rule\Shared\CallMethodNameResolver;
use PhpAiToolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver;
use PhpAiToolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher;
use PhpAiToolkit\PhpStan\Rule\TestAssertion\BrokenCodeExceptionClassifier;
use PhpAiToolkit\PhpStan\Rule\TestAssertion\ExpectExceptionCallInspector;
use PhpAiToolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationRule;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<NoBrokenCodeExpectationRule>
 * @covers \PhpAiToolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationRule
 * @uses \PhpAiToolkit\PhpStan\Rule\TestAssertion\BrokenCodeExceptionClassifier
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\CallArgumentResolver
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\CallMethodNameResolver
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver
 * @uses \PhpAiToolkit\PhpStan\Rule\TestAssertion\ExpectExceptionCallInspector
 * @uses \PhpAiToolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher
 * @uses \PhpAiToolkit\PhpStan\Support\TestClassScope
 */
#[CoversClass(NoBrokenCodeExpectationRule::class)]
#[UsesClass(BrokenCodeExceptionClassifier::class)]
#[UsesClass(CallArgumentResolver::class)]
#[UsesClass(CallMethodNameResolver::class)]
#[UsesClass(ClassStringExpressionResolver::class)]
#[UsesClass(ExpectExceptionCallInspector::class)]
#[UsesClass(NoBrokenCodeExpectationErrorBuilder::class)]
#[UsesClass(PhpUnitCallTargetMatcher::class)]
#[UsesClass(TestClassScope::class)]
#[Medium]
final class NoBrokenCodeExpectationRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new NoBrokenCodeExpectationRule(new TestClassScope());
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Expr::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeBrokenCodeExpectationsAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/NoBrokenCodeExpectation/WithBrokenCodeExpectation.php'], [
            [
                'Delete this test case instead of expecting "Throwable" in expectException(): Throwable matches every failure, so a passing test says nothing about what the code under test did. Keep only expectations for failures the code under test declares as behavior, such as a RuntimeException subclass.',
                18,
            ],
            [
                'Delete this test case instead of expecting "LogicException" in expectException(): LogicException is a programmer error (LogicException family) that only occurs while the code under test is broken. Keep only expectations for failures the code under test declares as behavior, such as a RuntimeException subclass.',
                23,
            ],
            [
                'Delete this test case instead of expecting "InvalidArgumentException" in expectException(): InvalidArgumentException is a programmer error (LogicException family) that only occurs while the code under test is broken. Keep only expectations for failures the code under test declares as behavior, such as a RuntimeException subclass.',
                28,
            ],
            [
                'Delete this test case instead of expecting "TypeError" in expectException(): TypeError is an engine failure (Error family) that only occurs while the code under test is broken. Keep only expectations for failures the code under test declares as behavior, such as a RuntimeException subclass.',
                33,
            ],
            [
                'Delete this test case instead of expecting "DivisionByZeroError" in expectExceptionObject(): DivisionByZeroError is an engine failure (Error family) that only occurs while the code under test is broken. Keep only expectations for failures the code under test declares as behavior, such as a RuntimeException subclass.',
                38,
            ],
        ]);
    }

    public function testProcessNodeBehaviorExpectationsAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/NoBrokenCodeExpectation/WithBehaviorExpectation.php'], []);
    }

    public function testProcessNodeOutsideTestNamespaceIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/NoBrokenCodeExpectation/NonTestClass.php'], []);
    }
}
