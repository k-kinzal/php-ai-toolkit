<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use ErrorException;
use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\Execution\DiagnosticLog;
use PhpAiToolkit\Doctest\Execution\ExecutionContext;
use PhpAiToolkit\Doctest\Execution\ExpressionEvaluator;
use PhpAiToolkit\Doctest\Execution\ReturnPolicy;
use PhpAiToolkit\Doctest\Execution\SourceSyntax;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExpressionEvaluator::class)]
#[UsesClass(ReturnPolicy::class)]
#[UsesClass(SourceSyntax::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(ExecutionContext::class)]
#[UsesClass(DiagnosticLog::class)]
final class ExpressionEvaluatorTest extends TestCase
{
    /**
     * @throws ErrorException
     */
    public function testEvaluateReturnsTheValueOfAnExpression(): void
    {
        self::assertSame(3, (new ExpressionEvaluator())->evaluate('', '1 + 2', new ExecutionContext()));
    }

    /**
     * @throws ErrorException
     */
    public function testEvaluateCarriesVariablesBetweenStatements(): void
    {
        $evaluator = new ExpressionEvaluator();
        $context = new ExecutionContext();

        $evaluator->evaluate('', '$sum = 1 + 2;', $context);

        self::assertSame(['sum' => 3], $context->variables());
        self::assertSame(6, $evaluator->evaluate('', '$sum * 2', $context));
    }

    /**
     * @throws ErrorException
     */
    public function testEvaluateCapturesWhatTheStatementPrinted(): void
    {
        $context = new ExecutionContext();

        (new ExpressionEvaluator())->evaluate('', 'echo "printed";', $context);

        self::assertSame('printed', $context->output());
    }

    /**
     * @throws ErrorException
     */
    public function testEvaluateResolvesNamesThroughThePreamble(): void
    {
        $preamble = "namespace Tests\\Fixture\\Doctest\\Project;\n";

        self::assertSame(3, (new ExpressionEvaluator())->evaluate($preamble, '(new Calculator())->add(1, 2)', new ExecutionContext()));
    }

    /**
     * @throws ErrorException
     */
    public function testEvaluateSourceReportsADiagnosticAsAThrownException(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined variable $missing');

        (new ExpressionEvaluator())->evaluateSource('return $missing;', []);
    }

    /**
     * @throws ErrorException
     */
    public function testEvaluateSourceReturnsTheValueVariablesAndOutput(): void
    {
        $evaluated = (new ExpressionEvaluator())->evaluateSource('echo "out"; $kept = 5; return 7;', ['given' => 1]);

        self::assertSame(7, $evaluated['value']);
        self::assertSame('out', $evaluated['output']);
        self::assertSame(['given' => 1, 'kept' => 5], $evaluated['variables']);
    }

    public function testEvaluatorKeepsNothingOfItsOwnScope(): void
    {
        $evaluated = (new ExpressionEvaluator())->evaluator()(['given' => 1], 'return 2;');

        self::assertSame(2, $evaluated['value']);
        self::assertSame(['given' => 1], $evaluated['variables']);
    }
}
