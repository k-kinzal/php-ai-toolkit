<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Executor;

use InvalidArgumentException;
use PhpAiToolkit\Doctest\Assertion\Assertion;
use PhpAiToolkit\Doctest\Assertion\AssertionKind;
use PhpAiToolkit\Doctest\Assertion\AssertionParser;
use PhpAiToolkit\Doctest\Assertion\AssertionResult;
use PhpAiToolkit\Doctest\Assertion\ParsedExample;
use PhpAiToolkit\Doctest\Assertion\Statement;
use PhpAiToolkit\Doctest\Executor\ExampleExecutor;
use PhpAiToolkit\Doctest\Executor\ExceptionMatcher;
use PhpAiToolkit\Doctest\Executor\ExecutionContext;
use PhpAiToolkit\Doctest\Executor\ExecutionResult;
use PhpAiToolkit\Doctest\Executor\ExpressionEvaluator;
use PhpAiToolkit\Doctest\Parser\Example;
use PhpAiToolkit\Doctest\Scanner\Target;
use PhpAiToolkit\Doctest\Scanner\TargetKind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ExampleExecutor::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(Statement::class)]
#[UsesClass(Assertion::class)]
#[UsesClass(AssertionParser::class)]
#[UsesClass(AssertionResult::class)]
#[UsesClass(ParsedExample::class)]
#[UsesClass(ExecutionResult::class)]
#[UsesClass(ExecutionContext::class)]
#[UsesClass(ExpressionEvaluator::class)]
#[UsesClass(ExceptionMatcher::class)]
#[Medium]
final class ExampleExecutorTest extends TestCase
{
    public function testExecuteRunsAnExampleWhoseAssertionsHold(): void
    {
        $path = (string) realpath(__DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php');
        $target = new Target(TargetKind::CLASS_LIKE, $path, '/** */', 'Calculator', 12, 'Tests\Fixture\Doctest\Project');
        $code = "\$calculator = new \\Tests\\Fixture\\Doctest\\Project\\Calculator();\n\$calculator->add(1, 2) // => 3";

        $result = (new ExampleExecutor())->execute(new Example($code, $target, 14, 0));

        self::assertTrue($result->passed);
    }

    public function testExecuteReportsAnExampleThatDocumentsTheWrongValue(): void
    {
        $path = (string) realpath(__DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php');
        $target = new Target(TargetKind::CLASS_LIKE, $path, '/** */', 'Calculator', 12, 'Tests\Fixture\Doctest\Project');

        $result = (new ExampleExecutor())->execute(new Example('(new \\Tests\\Fixture\\Doctest\\Project\\Calculator())->add(1, 2) // => 4', $target, 14, 0));

        self::assertFalse($result->passed);
        self::assertStringContainsString('Values do not match', $result->getErrorMessage());
    }

    public function testExecuteParsedRunsEveryStatementAndCollectsFailures(): void
    {
        $path = (string) realpath(__DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php');
        $target = new Target(TargetKind::CLASS_LIKE, $path, '/** */', 'Calculator', 12);
        $example = new Example('x', $target, 14, 0);
        $parsed = new ParsedExample($example, [
            new Statement('1 + 1', new Assertion(AssertionKind::RETURN_VALUE, '3'), 1),
            new Statement('1 + 1', new Assertion(AssertionKind::RETURN_VALUE, '2'), 2),
        ]);

        $result = (new ExampleExecutor())->executeParsed($parsed);

        self::assertFalse($result->passed);
        self::assertCount(1, $result->failures);
    }

    public function testLoadSourcesRequiresTheBootstrapAndTheTargetOnce(): void
    {
        $bootstrap = sys_get_temp_dir() . '/doctest-executor-bootstrap.php';
        file_put_contents($bootstrap, "<?php\n\$GLOBALS['doctestExecutorBootstrapCount'] = (\$GLOBALS['doctestExecutorBootstrapCount'] ?? 0) + 1;\n");
        $target = sys_get_temp_dir() . '/doctest-executor-target.php';
        file_put_contents($target, "<?php\n");
        $executor = new ExampleExecutor($bootstrap);

        $executor->loadSources($target);
        $executor->loadSources($target);

        self::assertSame(1, $GLOBALS['doctestExecutorBootstrapCount']);
    }

    public function testExecuteStatementReportsAnUnexpectedException(): void
    {
        $statement = new Statement('throw new \RuntimeException("bad");', null, 1);

        $result = (new ExampleExecutor())->executeStatement($statement, new ExecutionContext());

        self::assertFalse($result->passed);
        self::assertSame('Unexpected exception RuntimeException: bad', $result->message);
    }

    public function testExecuteStatementPassesASmokeStatement(): void
    {
        $result = (new ExampleExecutor())->executeStatement(new Statement('$sum = 1;', null, 1), new ExecutionContext());

        self::assertTrue($result->passed);
    }

    public function testExecuteWithExpectedExceptionReportsAStatementThatCompletes(): void
    {
        $statement = new Statement('1 + 1', new Assertion(AssertionKind::EXCEPTION, 'RuntimeException'), 1);

        $result = (new ExampleExecutor())->executeWithExpectedException($statement, new ExecutionContext());

        self::assertFalse($result->passed);
        self::assertSame('Expected exception RuntimeException but none was thrown', $result->message);
    }

    public function testExecuteWithExpectedExceptionAcceptsTheDocumentedException(): void
    {
        $statement = new Statement('throw new \InvalidArgumentException("Cannot divide by zero");', new Assertion(AssertionKind::EXCEPTION, 'InvalidArgumentException', 'divide by zero'), 1);

        $result = (new ExampleExecutor())->executeWithExpectedException($statement, new ExecutionContext());

        self::assertTrue($result->passed);
    }

    public function testCheckThrownExceptionReportsTheWrongClassAndTheWrongMessage(): void
    {
        $executor = new ExampleExecutor();
        $statement = new Statement('boom()', null, 1);

        $wrongClass = $executor->checkThrownException($statement, new RuntimeException('bad'), 'InvalidArgumentException', null);
        $wrongMessage = $executor->checkThrownException($statement, new InvalidArgumentException('bad'), 'InvalidArgumentException', 'overflow');

        self::assertFalse($wrongClass->passed);
        self::assertStringContainsString('but got RuntimeException', $wrongClass->message);
        self::assertFalse($wrongMessage->passed);
        self::assertStringContainsString('does not contain "overflow"', $wrongMessage->message);
    }

    public function testCheckAssertionRoutesOutputAndReturnValueAssertions(): void
    {
        $executor = new ExampleExecutor();
        $context = new ExecutionContext();
        $context->lastOutput = 'Hello';

        $output = $executor->checkAssertion(new Statement('echo "Hello";', new Assertion(AssertionKind::OUTPUT, 'Hello'), 1), null, $context);
        $returned = $executor->checkAssertion(new Statement('1 + 1', new Assertion(AssertionKind::RETURN_VALUE, '2'), 1), 2, $context);

        self::assertTrue($output->passed);
        self::assertTrue($returned->passed);
    }

    public function testCheckReturnValueReportsAnUnparsableExpectedValue(): void
    {
        $statement = new Statement('run()', new Assertion(AssertionKind::RETURN_VALUE, 'not php +'), 1);

        $result = (new ExampleExecutor())->checkReturnValue($statement, null);

        self::assertFalse($result->passed);
        self::assertStringContainsString('Failed to parse expected value', $result->message);
        self::assertStringContainsString('in: return not php +;', $result->message);
    }

    public function testCheckOutputAcceptsAnExactMatchOrOneTrailingNewline(): void
    {
        $executor = new ExampleExecutor();
        $statement = new Statement('echo "hi";', new Assertion(AssertionKind::OUTPUT, 'hi'), 1);

        self::assertTrue($executor->checkOutput($statement, 'hi')->passed);
        self::assertTrue($executor->checkOutput($statement, "hi\n")->passed);
        self::assertFalse($executor->checkOutput($statement, 'bye')->passed);
    }
}
