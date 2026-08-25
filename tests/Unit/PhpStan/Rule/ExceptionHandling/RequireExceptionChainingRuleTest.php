<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ExceptionHandling;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowCollector;
use Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowVisitor;
use Toolkit\PhpStan\Rule\ExceptionHandling\RequireExceptionChainingRule;
use Toolkit\PhpStan\Rule\ExceptionHandling\ThrowChainEvaluator;

/**
 * @extends RuleTestCase<RequireExceptionChainingRule>
 * @covers \Toolkit\PhpStan\Rule\ExceptionHandling\RequireExceptionChainingRule
 * @uses \Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowCollector
 * @uses \Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowVisitor
 * @uses \Toolkit\PhpStan\Rule\ExceptionHandling\ThrowChainEvaluator
 */
#[CoversClass(RequireExceptionChainingRule::class)]
#[UsesClass(CatchThrowCollector::class)]
#[UsesClass(CatchThrowVisitor::class)]
#[UsesClass(ThrowChainEvaluator::class)]
#[Medium]
final class RequireExceptionChainingRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new RequireExceptionChainingRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\Catch_::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeUnchainedThrowsAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/RequireExceptionChaining/WithUnchainedThrow.php'], [
            [
                'Pass the caught $exception to the exception thrown in this catch block, e.g. as the $previous constructor argument. Throwing a new exception without chaining discards the original failure and its stack trace.',
                17,
            ],
            [
                'Bind the caught RuntimeException to a variable and pass it to the exception thrown in this catch block, e.g. as the $previous constructor argument. Throwing a new exception without chaining discards the original failure and its stack trace.',
                26,
            ],
        ]);
    }

    public function testProcessNodeChainedThrowsAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/RequireExceptionChaining/WithChainedThrow.php'], []);
    }
}
