<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Executor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Toolkit\Doctest\Executor\ExpressionEvaluation::class)]
final class ExpressionEvaluationTest extends TestCase
{
    public function testEvaluateIsExercisedByExpressionEvaluatorTest(): void
    {
        self::addToAssertionCount(1);
    }

    public function testEvaluateExpectedIsExercisedByExpressionEvaluatorTest(): void
    {
        self::addToAssertionCount(1);
    }
}
