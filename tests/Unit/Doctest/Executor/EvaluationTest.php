<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Executor;

use PhpAiToolkit\Doctest\Executor\Evaluation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Evaluation::class)]
final class EvaluationTest extends TestCase
{
    public function testCompletedIsTrueWhenTheCodeProducedAValue(): void
    {
        $evaluation = new Evaluation(42);

        self::assertTrue($evaluation->completed());
        self::assertSame(42, $evaluation->value);
        self::assertNull($evaluation->error);
    }

    public function testCompletedIsFalseWhenTheCodeRaised(): void
    {
        $raised = new RuntimeException('bad');
        $evaluation = new Evaluation(null, $raised);

        self::assertFalse($evaluation->completed());
        self::assertSame($raised, $evaluation->error);
        self::assertNull($evaluation->value);
    }

    public function testDefaultsToAValuelessCompletion(): void
    {
        self::assertTrue((new Evaluation())->completed());
    }
}
