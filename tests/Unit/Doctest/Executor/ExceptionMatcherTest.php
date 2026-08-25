<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Executor;

use InvalidArgumentException;
use LogicException;
use PhpAiToolkit\Doctest\Executor\ExceptionMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fixture\Doctest\RuntimeException as CollidingRuntimeException;

/**
 * @covers \PhpAiToolkit\Doctest\Executor\ExceptionMatcher
 */
#[CoversClass(ExceptionMatcher::class)]
final class ExceptionMatcherTest extends TestCase
{
    public function testMatchesAcceptsTheClassAndItsParents(): void
    {
        $matcher = new ExceptionMatcher();
        $thrown = new InvalidArgumentException('bad');

        self::assertTrue($matcher->matches($thrown, InvalidArgumentException::class));
        self::assertTrue($matcher->matches($thrown, LogicException::class));
    }

    public function testMatchesFallsBackToTheShortNameForAnUnresolvableClass(): void
    {
        $matcher = new ExceptionMatcher();

        self::assertTrue($matcher->matches(new InvalidArgumentException('bad'), 'InvalidArgumentException'));
        self::assertFalse($matcher->matches(new InvalidArgumentException('bad'), 'NoSuchException'));
    }

    public function testMatchesRejectsAnUnrelatedResolvableClass(): void
    {
        self::assertFalse((new ExceptionMatcher())->matches(new InvalidArgumentException('bad'), RuntimeException::class));
    }

    public function testMatchesRejectsAShortNameThatResolvesToAnotherClass(): void
    {
        self::assertFalse((new ExceptionMatcher())->matches(new CollidingRuntimeException('bad'), 'RuntimeException'));
    }
}
