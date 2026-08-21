<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Execution\ExecutionContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExecutionContext::class)]
final class ExecutionContextTest extends TestCase
{
    public function testVariablesStartEmptyAndAreCarriedForward(): void
    {
        $context = new ExecutionContext();

        self::assertSame([], $context->variables());

        $context->remember(['sum' => 3]);

        self::assertSame(['sum' => 3], $context->variables());
    }

    public function testRememberReplacesEverythingKnownSoFar(): void
    {
        $context = new ExecutionContext();
        $context->remember(['sum' => 3]);
        $context->remember(['total' => 7]);

        self::assertSame(['total' => 7], $context->variables());
    }

    public function testOutputStartsEmpty(): void
    {
        self::assertSame('', (new ExecutionContext())->output());
    }

    public function testCaptureRecordsWhatTheLastStatementPrinted(): void
    {
        $context = new ExecutionContext();
        $context->capture("Hello\n");

        self::assertSame("Hello\n", $context->output());
    }
}
