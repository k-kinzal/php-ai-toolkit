<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Toolkit\PhpStan\Rule\TestAssertion\BrokenCodeExceptionClassifier;

/**
 * @covers \Toolkit\PhpStan\Rule\TestAssertion\BrokenCodeExceptionClassifier
 * @medium
 */
#[CoversClass(BrokenCodeExceptionClassifier::class)]
#[Medium]
final class BrokenCodeExceptionClassifierTest extends PHPStanTestCase
{
    public function testReasonDescribesThrowable(): void
    {
        self::assertSame(
            'Throwable matches every failure, so a passing test says nothing about what the code under test did',
            (new BrokenCodeExceptionClassifier())->reason('Throwable'),
        );
    }

    public function testReasonDescribesLogicExceptionFamily(): void
    {
        self::createReflectionProvider();

        self::assertSame(
            'InvalidArgumentException is a programmer error (LogicException family) that only occurs while the code under test is broken',
            (new BrokenCodeExceptionClassifier())->reason('InvalidArgumentException'),
        );
    }

    public function testReasonDescribesErrorFamily(): void
    {
        self::createReflectionProvider();

        self::assertSame(
            'TypeError is an engine failure (Error family) that only occurs while the code under test is broken',
            (new BrokenCodeExceptionClassifier())->reason('TypeError'),
        );
    }

    public function testReasonAcceptsRuntimeExceptionFamily(): void
    {
        self::createReflectionProvider();

        self::assertNull((new BrokenCodeExceptionClassifier())->reason('RuntimeException'));
    }

    public function testReasonAcceptsRootException(): void
    {
        self::createReflectionProvider();

        self::assertNull((new BrokenCodeExceptionClassifier())->reason('Exception'));
    }

    public function testReasonAcceptsUnknownClass(): void
    {
        self::createReflectionProvider();

        self::assertNull((new BrokenCodeExceptionClassifier())->reason('App\\ReportSourceUnreadable'));
    }
}
