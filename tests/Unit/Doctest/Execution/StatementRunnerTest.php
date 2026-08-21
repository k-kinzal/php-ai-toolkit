<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use InvalidArgumentException;
use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\Execution\DiagnosticLog;
use PhpAiToolkit\Doctest\Execution\ExceptionMatcher;
use PhpAiToolkit\Doctest\Execution\ExecutionContext;
use PhpAiToolkit\Doctest\Execution\ExpressionEvaluator;
use PhpAiToolkit\Doctest\Execution\ReturnPolicy;
use PhpAiToolkit\Doctest\Execution\RunFailure;
use PhpAiToolkit\Doctest\Execution\SourceSyntax;
use PhpAiToolkit\Doctest\Execution\Statement;
use PhpAiToolkit\Doctest\Execution\StatementRunner;
use PhpAiToolkit\Doctest\Execution\ValueFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(StatementRunner::class)]
#[UsesClass(Statement::class)]
#[UsesClass(RunFailure::class)]
#[UsesClass(ExpressionEvaluator::class)]
#[UsesClass(ExceptionMatcher::class)]
#[UsesClass(ValueFormatter::class)]
#[UsesClass(ExecutionContext::class)]
#[UsesClass(ReturnPolicy::class)]
#[UsesClass(SourceSyntax::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(DiagnosticLog::class)]
final class StatementRunnerTest extends TestCase
{
    public function testRunPassesASmokeStatementThatCompletes(): void
    {
        $failure = (new StatementRunner())->run(new Statement('$sum = 1 + 2;', null, null, null, 1), '', new ExecutionContext());

        self::assertNull($failure);
    }

    public function testRunReportsAStatementThatRaises(): void
    {
        $failure = (new StatementRunner())->run(new Statement('throw new \RuntimeException("bad");', null, null, null, 2), '', new ExecutionContext());

        self::assertNotNull($failure);
        self::assertSame('Statement raised RuntimeException: bad', $failure->message);
        self::assertSame(2, $failure->line);
    }

    public function testRunChecksTheDocumentedValueAndOutput(): void
    {
        $runner = new StatementRunner();

        self::assertNull($runner->run(new Statement('1 + 2', 'return', '3', null, 1), '', new ExecutionContext()));
        self::assertNull($runner->run(new Statement('echo "hi";', 'output', 'hi', null, 1), '', new ExecutionContext()));
    }

    public function testRunExpectingExceptionAcceptsTheDocumentedException(): void
    {
        $statement = new Statement('throw new \InvalidArgumentException("Cannot divide by zero");', 'throws', 'InvalidArgumentException', 'divide by zero', 1);

        self::assertNull((new StatementRunner())->runExpectingException($statement, '', new ExecutionContext()));
    }

    public function testRunExpectingExceptionReportsAStatementThatCompletes(): void
    {
        $statement = new Statement('1 + 1', 'throws', 'RuntimeException', null, 3);

        $failure = (new StatementRunner())->runExpectingException($statement, '', new ExecutionContext());

        self::assertNotNull($failure);
        self::assertSame('Expected RuntimeException to be thrown, but the statement completed.', $failure->message);
        self::assertSame('no exception', $failure->actual);
    }

    public function testCheckThrownReportsTheWrongExceptionClass(): void
    {
        $statement = new Statement('boom()', 'throws', 'InvalidArgumentException', null, 4);

        $failure = (new StatementRunner())->checkThrown($statement, new RuntimeException('bad'), 'InvalidArgumentException');

        self::assertNotNull($failure);
        self::assertSame('Expected InvalidArgumentException to be thrown, but RuntimeException was thrown instead.', $failure->message);
    }

    public function testCheckThrownReportsAMessageThatDoesNotCarryTheDocumentedText(): void
    {
        $statement = new Statement('boom()', 'throws', 'InvalidArgumentException', 'overflow', 4);

        $failure = (new StatementRunner())->checkThrown($statement, new InvalidArgumentException('bad input'), 'InvalidArgumentException');

        self::assertNotNull($failure);
        self::assertSame('Expected the InvalidArgumentException message to contain the documented text.', $failure->message);
        self::assertSame('overflow', $failure->expected);
        self::assertSame('bad input', $failure->actual);
    }

    public function testCheckReturnValueComparesStrictly(): void
    {
        $runner = new StatementRunner();

        self::assertNull($runner->checkReturnValue(new Statement('1 + 2', 'return', '3', null, 1), 3, ''));
        $failure = $runner->checkReturnValue(new Statement('1 + 2', 'return', '3', null, 1), '3', '');

        self::assertNotNull($failure);
        self::assertSame('The statement did not produce the documented value.', $failure->message);
        self::assertSame('3', $failure->expected);
        self::assertSame("'3'", $failure->actual);
    }

    public function testCheckReturnValueReportsADocumentedValueItCannotEvaluate(): void
    {
        $failure = (new StatementRunner())->checkReturnValue(new Statement('run()', 'return', 'not php +', null, 1), null, '');

        self::assertNotNull($failure);
        self::assertStringContainsString('The documented value "not php +" could not be evaluated', $failure->message);
    }

    public function testCheckOutputAcceptsAnExactMatchOrOneTrailingNewline(): void
    {
        $runner = new StatementRunner();
        $statement = new Statement('echo "hi";', 'output', 'hi', null, 1);

        self::assertNull($runner->checkOutput($statement, 'hi'));
        self::assertNull($runner->checkOutput($statement, "hi\n"));

        $failure = $runner->checkOutput($statement, 'bye');

        self::assertNotNull($failure);
        self::assertSame('The statement did not print the documented output.', $failure->message);
    }
}
