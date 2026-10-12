<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Executor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Assertion\AssertionResult;
use Toolkit\Doctest\Assertion\Statement;
use Toolkit\Doctest\Executor\ExecutionResult;
use Toolkit\Doctest\Parser\Example;
use Toolkit\Doctest\Scanner\Target;
use Toolkit\Doctest\Scanner\TargetKind;

/**
 * @covers \Toolkit\Doctest\Executor\ExecutionResult
 * @uses \Toolkit\Doctest\Parser\Example
 * @uses \Toolkit\Doctest\Scanner\Target
 * @uses \Toolkit\Doctest\Assertion\AssertionResult
 * @uses \Toolkit\Doctest\Assertion\Statement
 */
#[CoversClass(ExecutionResult::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(AssertionResult::class)]
#[UsesClass(Statement::class)]
final class ExecutionResultTest extends TestCase
{
    public function testGetErrorMessageIsEmptyForAPassingResult(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Widget', 1);
        $result = new ExecutionResult(new Example('1+1', $target, 1, 0), true);

        self::assertTrue($result->passed);
        self::assertSame('', $result->getErrorMessage());
        self::assertSame([], $result->failures);
    }

    public function testGetErrorMessageJoinsEveryFailure(): void
    {
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Widget', 1);
        $example = new Example('1+1', $target, 1, 0);
        $failures = [
            new AssertionResult(false, 'Values do not match', new Statement('1+1', null, 1), 3, 2),
            new AssertionResult(false, 'Output does not match', new Statement('echo 1;', null, 2)),
        ];

        $result = new ExecutionResult($example, false, $failures);

        self::assertSame($example, $result->example);
        self::assertStringContainsString('Values do not match', $result->getErrorMessage());
        self::assertStringContainsString('Output does not match', $result->getErrorMessage());
    }
}
