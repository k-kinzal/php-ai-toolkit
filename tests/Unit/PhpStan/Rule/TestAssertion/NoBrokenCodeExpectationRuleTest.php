<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\Shared\CallArgumentResolver;
use Toolkit\PhpStan\Rule\Shared\CallMethodNameResolver;
use Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver;
use Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher;
use Toolkit\PhpStan\Rule\TestAssertion\BrokenCodeExceptionClassifier;
use Toolkit\PhpStan\Rule\TestAssertion\ExpectExceptionCallInspector;
use Toolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationErrorBuilder;
use Toolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationRule;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * @extends RuleTestCase<NoBrokenCodeExpectationRule>
 * @covers \Toolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationRule
 * @uses \Toolkit\PhpStan\Rule\TestAssertion\BrokenCodeExceptionClassifier
 * @uses \Toolkit\PhpStan\Rule\Shared\CallArgumentResolver
 * @uses \Toolkit\PhpStan\Rule\Shared\CallMethodNameResolver
 * @uses \Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver
 * @uses \Toolkit\PhpStan\Rule\TestAssertion\ExpectExceptionCallInspector
 * @uses \Toolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher
 * @uses \Toolkit\PhpStan\Support\TestClassScope
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
