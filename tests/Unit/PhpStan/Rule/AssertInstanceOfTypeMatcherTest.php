<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\AssertInstanceOfTypeMatcher;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssertInstanceOfTypeMatcher::class)]
final class AssertInstanceOfTypeMatcherTest extends TestCase
{
    public function testMatchesReturnsTrueForSameClassNameWithoutReflectionProvider(): void
    {
        self::assertTrue((new AssertInstanceOfTypeMatcher())->matches(
            'App\\Service',
            new ObjectType('App\\Service'),
            'App\\Service',
        ));
    }

    public function testMatchesReturnsFalseWhenReflectionProviderIsUnavailable(): void
    {
        self::assertFalse((new AssertInstanceOfTypeMatcher())->matches(
            'App\\Contract',
            new ObjectType('App\\Service'),
            'App\\Service',
        ));
    }
}
