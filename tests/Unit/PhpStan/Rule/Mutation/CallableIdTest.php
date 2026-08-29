<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Mutation;

use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Mutation\CallableId;

#[CoversClass(CallableId::class)]
final class CallableIdTest extends TestCase
{
    public function testCurrentReturnsNullOutsideACallable(): void
    {
        self::assertNull((new CallableId())->current(self::createStub(Scope::class)));
    }

    public function testFunctionNormalizesCaseAndLeadingSeparator(): void
    {
        self::assertSame('function:app\\run', (new CallableId())->function('\\App\\Run'));
    }

    public function testMethodNormalizesClassAndMethodNames(): void
    {
        self::assertSame('method:app\\runner::run', (new CallableId())->method('\\App\\Runner', 'Run'));
    }
}
