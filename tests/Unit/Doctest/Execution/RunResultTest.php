<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\Execution\RunFailure;
use PhpAiToolkit\Doctest\Execution\RunResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RunResult::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(RunFailure::class)]
final class RunResultTest extends TestCase
{
    public function testPassedIsTrueWhenNoAssertionFailed(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag', 0), 6);

        self::assertTrue((new RunResult($example, []))->passed());
        self::assertFalse((new RunResult($example, [new RunFailure('run()', 1, 'nope')]))->passed());
    }

    public function testErrorMessageIsEmptyForAPassingResult(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag', 0), 6);

        self::assertSame('', (new RunResult($example, []))->errorMessage());
    }

    public function testErrorMessageDescribesEveryFailedAssertion(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag', 0), 6);
        $result = new RunResult($example, [
            new RunFailure('add(1, 2)', 3, 'Values differ.', '3', '4'),
            new RunFailure('boom()', 5, 'Statement raised RuntimeException: bad'),
        ]);

        self::assertSame(
            "line 3: Values differ.\n  code: add(1, 2)\n  expected: 3\n  actual: 4\nline 5: Statement raised RuntimeException: bad\n  code: boom()",
            $result->errorMessage(),
        );
    }

    public function testExposesTheExampleAndWhetherItWasSkipped(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag-inline', 0), 6);
        $result = new RunResult($example, [], true);

        self::assertSame($example, $result->example);
        self::assertSame([], $result->failures);
        self::assertTrue($result->skipped);
    }
}
