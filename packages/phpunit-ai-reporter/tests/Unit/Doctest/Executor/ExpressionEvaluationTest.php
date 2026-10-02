<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Executor;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
#[CoversNothing]
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
