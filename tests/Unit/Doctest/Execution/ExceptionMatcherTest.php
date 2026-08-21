<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use InvalidArgumentException;
use LogicException;
use PhpAiToolkit\Doctest\Execution\ExceptionMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

#[CoversClass(ExceptionMatcher::class)]
final class ExceptionMatcherTest extends TestCase
{
    public function testMatchesAcceptsTheClassItsParentsAndItsInterfaces(): void
    {
        $matcher = new ExceptionMatcher();
        $thrown = new InvalidArgumentException('bad input');

        self::assertTrue($matcher->matches($thrown, InvalidArgumentException::class));
        self::assertTrue($matcher->matches($thrown, LogicException::class));
        self::assertTrue($matcher->matches($thrown, Throwable::class));
    }

    public function testMatchesAcceptsAShortNameAndALeadingBackslash(): void
    {
        $matcher = new ExceptionMatcher();
        $thrown = new InvalidArgumentException('bad input');

        self::assertTrue($matcher->matches($thrown, 'InvalidArgumentException'));
        self::assertTrue($matcher->matches($thrown, '\InvalidArgumentException'));
    }

    public function testMatchesRejectsAnUnrelatedClass(): void
    {
        $matcher = new ExceptionMatcher();

        self::assertFalse($matcher->matches(new InvalidArgumentException('bad'), RuntimeException::class));
        self::assertFalse($matcher->matches(new InvalidArgumentException('bad'), 'Nope'));
        self::assertFalse($matcher->matches(new InvalidArgumentException('bad'), ''));
    }

    public function testMatchesMessageAcceptsAFragmentAndAnAbsentExpectation(): void
    {
        $matcher = new ExceptionMatcher();
        $thrown = new RuntimeException('Cannot divide by zero');

        self::assertTrue($matcher->matchesMessage($thrown, 'divide by zero'));
        self::assertTrue($matcher->matchesMessage($thrown, null));
        self::assertTrue($matcher->matchesMessage($thrown, ''));
        self::assertFalse($matcher->matchesMessage($thrown, 'overflow'));
    }

    public function testLineageListsTheClassItsParentsAndItsInterfaces(): void
    {
        $lineage = (new ExceptionMatcher())->lineage(new InvalidArgumentException('bad'));

        self::assertContains(InvalidArgumentException::class, $lineage);
        self::assertContains(LogicException::class, $lineage);
        self::assertContains(Throwable::class, $lineage);
    }

    public function testShortNameDropsTheNamespace(): void
    {
        $matcher = new ExceptionMatcher();

        self::assertSame('InvalidArgumentException', $matcher->shortName('InvalidArgumentException'));
        self::assertSame('Failure', $matcher->shortName('App\Billing\Failure'));
    }
}
