<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Executor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Executor\Evaluation;
use Toolkit\Doctest\Executor\ExecutionContext;
use Toolkit\Doctest\Executor\ExpressionEvaluator;

/**
 * @covers \Toolkit\Doctest\Executor\ExpressionEvaluator
 * @uses \Toolkit\Doctest\Executor\ExecutionContext
 * @uses \Toolkit\Doctest\Executor\Evaluation
 */
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
        self::assertFalse($evaluator->codeNeedsReturn('   echo $sum;'));
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

    /**
     * @dataProvider providerSideEffectCode
     */
    #[DataProvider('providerSideEffectCode')]
    public function testCodeNeedsReturnIsFalseForEverySideEffectItKnows(string $code): void
    {
        self::assertFalse((new ExpressionEvaluator())->codeNeedsReturn($code));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function providerSideEffectCode(): array
    {
        return [
            'assignment' => ['$sum = 1 + 2'],
            'indented assignment' => ['    $sum = 1 + 2'],
            'echo' => ['echo $sum'],
            'print' => ['print $sum'],
            'return' => ['return $sum'],
            'if' => ['if ($sum > 0) { report($sum); }'],
            'for' => ['for ($index = 0; $index < 3; $index++) { report($index); }'],
            'foreach' => ['foreach ($values as $value) { report($value); }'],
            'while' => ['while ($sum > 0) { report($sum); }'],
            'do' => ['do { report($sum); } while ($sum > 0)'],
            'switch' => ['switch ($sum) { default: report($sum); }'],
            'try' => ['try { report($sum); } catch (\RuntimeException $error) {}'],
            'throw' => ['throw new \RuntimeException("bad")'],
            'class declaration' => ['class Widget {}'],
            'function declaration' => ['function widget() {}'],
            'bare construction' => ['new Widget;'],
        ];
    }
}
