<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\Execution\ReturnPolicy;
use PhpAiToolkit\Doctest\Execution\SourceSyntax;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReturnPolicy::class)]
#[UsesClass(SourceSyntax::class)]
#[UsesClass(PhpParserBridge::class)]
final class ReturnPolicyTest extends TestCase
{
    public function testNeedsReturnIsTrueForASingleExpression(): void
    {
        $policy = new ReturnPolicy();

        self::assertTrue($policy->needsReturn('add(1, 2)'));
        self::assertTrue($policy->needsReturn('$ledger->append($entry);'));
        self::assertTrue($policy->needsReturn('$sum = 1 + 2'));
    }

    public function testNeedsReturnIsFalseForStatementsThatHaveNoValue(): void
    {
        $policy = new ReturnPolicy();

        self::assertFalse($policy->needsReturn('echo "hi";'));
        self::assertFalse($policy->needsReturn('foreach ([1] as $value) { echo $value; }'));
        self::assertFalse($policy->needsReturn('$a = 1; $b = 2;'));
        self::assertFalse($policy->needsReturn('final class Broken extends'));
    }

    public function testSourceWrapsAnExpressionInAReturn(): void
    {
        $policy = new ReturnPolicy();

        self::assertSame('return add(1, 2);', $policy->source('add(1, 2)'));
        self::assertSame('echo "hi";', $policy->source('echo "hi";'));
    }
}
