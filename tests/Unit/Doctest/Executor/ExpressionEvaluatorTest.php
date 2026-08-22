<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Executor;

use PhpAiToolkit\Doctest\Executor\Evaluation;
use PhpAiToolkit\Doctest\Executor\ExecutionContext;
use PhpAiToolkit\Doctest\Executor\ExpressionEvaluator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExpressionEvaluator::class)]
#[UsesClass(ExecutionContext::class)]
#[UsesClass(Evaluation::class)]
final class ExpressionEvaluatorTest extends TestCase
{
    public function testEvaluateReturnsTheValueOfAnExpression(): void
    {
        self::assertSame(3, (new ExpressionEvaluator())->evaluate('1 + 2', new ExecutionContext())->value);
    }

    public function testEvaluateCarriesVariablesAndOutputIntoTheContext(): void
    {
        $evaluator = new ExpressionEvaluator();
        $context = new ExecutionContext();

        $evaluator->evaluate('$sum = 1 + 2;', $context);

        self::assertSame(['sum' => 3], $context->getVariables());

        $evaluator->evaluate('echo $sum;', $context);

        self::assertSame('3', $context->lastOutput);
    }

    public function testEvaluateExpectedReadsTheDocumentedValue(): void
    {
        self::assertSame([1, 2], (new ExpressionEvaluator())->evaluateExpected('[1, 2]')->value);
    }

    public function testEvaluateHandsBackWhateverTheExampleRaised(): void
    {
        $evaluation = (new ExpressionEvaluator())->evaluate('throw new \RuntimeException("bad");', new ExecutionContext());

        self::assertFalse($evaluation->completed());
        self::assertNotNull($evaluation->error);
        self::assertSame('bad', $evaluation->error->getMessage());
    }

    public function testEvaluateLeavesTheContextUntouchedWhenTheCodeRaises(): void
    {
        $context = new ExecutionContext();
        $context->setVariable('kept', 1);

        (new ExpressionEvaluator())->evaluate('throw new \RuntimeException("bad");', $context);

        self::assertSame(['kept' => 1], $context->getVariables());
    }

    public function testEvaluateExpectedHandsBackASyntaxError(): void
    {
        $evaluation = (new ExpressionEvaluator())->evaluateExpected('not php +');

        self::assertFalse($evaluation->completed());
        self::assertNotNull($evaluation->error);
    }

    public function testCodeNeedsReturnIsFalseForCodeWithASideEffect(): void
    {
        $evaluator = new ExpressionEvaluator();

        self::assertFalse($evaluator->codeNeedsReturn('$sum = 1;'));
        self::assertFalse($evaluator->codeNeedsReturn('echo $sum;'));
        self::assertFalse($evaluator->codeNeedsReturn('foreach ($xs as $x) {}'));
        self::assertFalse($evaluator->codeNeedsReturn('$widget->render();'));
    }

    public function testCodeNeedsReturnIsTrueForABareExpression(): void
    {
        $evaluator = new ExpressionEvaluator();

        self::assertTrue($evaluator->codeNeedsReturn('add(1, 2)'));
        self::assertTrue($evaluator->codeNeedsReturn('$widget->render()'));
    }

    public function testEvaluateKeepsNoneOfItsOwnLocalsInTheExampleScope(): void
    {
        $context = new ExecutionContext();
        $context->setVariable('given', 1);

        (new ExpressionEvaluator())->evaluate('$kept = 2;', $context);

        self::assertSame(['given' => 1, 'kept' => 2], $context->getVariables());
    }
}
