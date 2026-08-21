<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Analysis\AssertionLine;
use PhpAiToolkit\Doctest\Analysis\AssertionScanner;
use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\Execution\DiagnosticLog;
use PhpAiToolkit\Doctest\Execution\ExampleRunner;
use PhpAiToolkit\Doctest\Execution\ExceptionMatcher;
use PhpAiToolkit\Doctest\Execution\ExecutionContext;
use PhpAiToolkit\Doctest\Execution\ExpressionEvaluator;
use PhpAiToolkit\Doctest\Execution\ReturnPolicy;
use PhpAiToolkit\Doctest\Execution\RunFailure;
use PhpAiToolkit\Doctest\Execution\RunResult;
use PhpAiToolkit\Doctest\Execution\SourceLoader;
use PhpAiToolkit\Doctest\Execution\SourceSyntax;
use PhpAiToolkit\Doctest\Execution\Statement;
use PhpAiToolkit\Doctest\Execution\StatementBuilder;
use PhpAiToolkit\Doctest\Execution\StatementRunner;
use PhpAiToolkit\Doctest\Execution\ValueFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExampleRunner::class)]
#[UsesClass(RunResult::class)]
#[UsesClass(RunFailure::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(StatementBuilder::class)]
#[UsesClass(StatementRunner::class)]
#[UsesClass(SourceLoader::class)]
#[UsesClass(Statement::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssertionLine::class)]
#[UsesClass(SourceSyntax::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(ExpressionEvaluator::class)]
#[UsesClass(ExceptionMatcher::class)]
#[UsesClass(ValueFormatter::class)]
#[UsesClass(ExecutionContext::class)]
#[UsesClass(ReturnPolicy::class)]
#[UsesClass(DiagnosticLog::class)]
final class ExampleRunnerTest extends TestCase
{
    public function testRunPassesAnExampleWhoseAssertionsHold(): void
    {
        $target = new Target(Target::METHOD, __DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php', '/** */', 'add', 20, 'Tests\Fixture\Doctest\Project', 'Calculator');
        $example = new Example($target, new DocExample(null, "\$calculator = new Calculator();\n\$calculator->add(1, 2) // => 3", 'tag', 0), 22);

        $result = (new ExampleRunner())->run($example);

        self::assertTrue($result->passed());
        self::assertFalse($result->skipped);
    }

    public function testRunReportsEveryBrokenAssertionOfTheExample(): void
    {
        $target = new Target(Target::METHOD, __DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php', '/** */', 'add', 20, 'Tests\Fixture\Doctest\Project', 'Calculator');
        $example = new Example($target, new DocExample(null, "(new Calculator())->add(1, 2) // => 4\n(new Calculator())->add(2, 2) // => 5", 'tag', 0), 22);

        $result = (new ExampleRunner())->run($example);

        self::assertFalse($result->passed());
        self::assertCount(2, $result->failures);
    }

    public function testRunSkipsADisplayOnlyExample(): void
    {
        $target = new Target(Target::METHOD, '/does/not/exist.php', '/** */', 'add', 20, 'App', 'Calculator');
        $example = new Example($target, new DocExample(null, '$calculator->add($left, $right)', 'tag-inline', 0), 22);

        $result = (new ExampleRunner())->run($example);

        self::assertTrue($result->skipped);
        self::assertTrue($result->passed());
    }
}
