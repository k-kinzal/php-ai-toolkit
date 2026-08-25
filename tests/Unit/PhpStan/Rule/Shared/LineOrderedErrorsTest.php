<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpAiToolkit\PhpStan\Rule\Shared\LineOrderedErrors;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\Shared\LineOrderedErrors
 */
#[CoversClass(LineOrderedErrors::class)]
final class LineOrderedErrorsTest extends TestCase
{
    public function testSortedOrdersErrorsByTheLineTheyPointAt(): void
    {
        $method = RuleErrorBuilder::message('method')->identifier('customRules.demo')->line(16)->build();
        $constant = RuleErrorBuilder::message('constant')->identifier('customRules.demo')->line(10)->build();
        $class = RuleErrorBuilder::message('class')->identifier('customRules.demo')->line(7)->build();

        self::assertSame([$class, $constant, $method], (new LineOrderedErrors())->sorted([$class, $method, $constant]));
    }

    public function testSortedKeepsAnEmptyListEmpty(): void
    {
        self::assertSame([], (new LineOrderedErrors())->sorted([]));
    }

    public function testLineOfReadsTheLineOfAnError(): void
    {
        $error = RuleErrorBuilder::message('demo')->identifier('customRules.demo')->line(42)->build();

        self::assertSame(42, (new LineOrderedErrors())->lineOf($error));
    }

    public function testLineOfReadsZeroForAnErrorWithoutALine(): void
    {
        $error = RuleErrorBuilder::message('demo')->identifier('customRules.demo')->build();

        self::assertSame(LineOrderedErrors::NO_LINE, (new LineOrderedErrors())->lineOf($error));
    }
}
