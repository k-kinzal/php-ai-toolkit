<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Toolkit\PhpStan\Rule\TestAssertion\AssertInstanceOfTypeMatcher;

/**
 * @covers \Toolkit\PhpStan\Rule\TestAssertion\AssertInstanceOfTypeMatcher
 * @medium
 */
#[CoversClass(AssertInstanceOfTypeMatcher::class)]
#[Medium]
final class AssertInstanceOfTypeMatcherTest extends PHPStanTestCase
{
    public function testMatchesReturnsTrueForSameClassName(): void
    {
        self::assertTrue((new AssertInstanceOfTypeMatcher())->matches(
            'App\\Service',
            new ObjectType('App\\Service'),
            'App\\Service',
        ));
    }

    public function testMatchesReturnsTrueForSupertype(): void
    {
        self::createReflectionProvider();

        self::assertTrue((new AssertInstanceOfTypeMatcher())->matches(
            'Exception',
            new ObjectType('RuntimeException'),
            'RuntimeException',
        ));
    }

    public function testMatchesReturnsFalseForUnrelatedClass(): void
    {
        self::createReflectionProvider();

        self::assertFalse((new AssertInstanceOfTypeMatcher())->matches(
            'LogicException',
            new ObjectType('RuntimeException'),
            'RuntimeException',
        ));
    }
}
