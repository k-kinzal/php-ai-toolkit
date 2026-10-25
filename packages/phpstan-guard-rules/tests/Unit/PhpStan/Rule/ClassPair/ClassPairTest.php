<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassPair;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassPair\ClassNameGlob;
use Toolkit\PhpStan\Rule\ClassPair\ClassNamePattern;
use Toolkit\PhpStan\Rule\ClassPair\ClassPair;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassPair\ClassPair
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassNameGlob
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassNamePattern
 */
#[CoversClass(ClassPair::class)]
#[UsesClass(ClassNameGlob::class)]
#[UsesClass(ClassNamePattern::class)]
final class ClassPairTest extends TestCase
{
    public function testFromPatternReturnsTheOriginTemplate(): void
    {
        $pair = new ClassPair(new ClassNamePattern('App\{name}'), new ClassNamePattern('Tests\Unit\{name}Test'), new ClassNameGlob([]));

        self::assertSame('App\{name}', $pair->fromPattern());
    }

    public function testToPatternReturnsTheCounterpartTemplate(): void
    {
        $pair = new ClassPair(new ClassNamePattern('App\{name}'), new ClassNamePattern('Tests\Unit\{name}Test'), new ClassNameGlob([]));

        self::assertSame('Tests\Unit\{name}Test', $pair->toPattern());
    }

    public function testExcludesMatchesConfiguredGlobs(): void
    {
        $pair = new ClassPair(new ClassNamePattern('App\{name}'), new ClassNamePattern('Tests\Unit\{name}Test'), new ClassNameGlob(['*Interface']));

        self::assertTrue($pair->excludes('App\MailerInterface'));
        self::assertFalse($pair->excludes('App\Mailer'));
    }

    public function testCounterpartOfMapsOriginClassesToTheirCounterpart(): void
    {
        $pair = new ClassPair(new ClassNamePattern('App\{name}'), new ClassNamePattern('Tests\Unit\{name}Test'), new ClassNameGlob([]));

        self::assertSame('Tests\Unit\Service\UserTest', $pair->counterpartOf('App\Service\User'));
        self::assertNull($pair->counterpartOf('Tests\Unit\Service\UserTest'));
    }

    public function testCounterpartOfIgnoresTemplatesThatMapAClassToItself(): void
    {
        $pair = new ClassPair(new ClassNamePattern('App\{name}'), new ClassNamePattern('App\{name}'), new ClassNameGlob([]));

        self::assertNull($pair->counterpartOf('App\Service\User'));
    }

    public function testOriginOfMapsCounterpartsBackToTheirOrigin(): void
    {
        $pair = new ClassPair(new ClassNamePattern('App\{name}'), new ClassNamePattern('Tests\Unit\{name}Test'), new ClassNameGlob([]));

        self::assertSame('App\Service\User', $pair->originOf('Tests\Unit\Service\UserTest'));
        self::assertNull($pair->originOf('App\Service\User'));
    }

    public function testOriginOfIgnoresTemplatesThatMapAClassToItself(): void
    {
        $pair = new ClassPair(new ClassNamePattern('App\{name}'), new ClassNamePattern('App\{name}'), new ClassNameGlob([]));

        self::assertNull($pair->originOf('App\Service\User'));
    }
}
