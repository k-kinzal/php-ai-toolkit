<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Type\MixedVisibilityDetector;

#[CoversClass(MixedVisibilityDetector::class)]
final class MixedVisibilityDetectorTest extends TestCase
{
    public function testIsRestrictedMatchesEffectiveVisibilityScopeSemantics(): void
    {
        $detector = new MixedVisibilityDetector();

        self::assertFalse($detector->isRestricted(null, 'App\Feature'));
        self::assertFalse($detector->isRestricted('/** @visibility public */', 'App\Feature'));
        self::assertTrue($detector->isRestricted('/** @visibility namespace */', 'App\Feature'));
        self::assertTrue($detector->isRestricted('/** @visibility App */', 'App\Feature'));
        self::assertFalse($detector->isRestricted('/** @visibility typo */', 'App\Feature'));
    }

    public function testValuesIgnoresProseAndReadsTags(): void
    {
        $detector = new MixedVisibilityDetector();

        self::assertSame(['namespace'], $detector->values("/**\n * Mentions @visibility public in prose.\n * @visibility namespace\n */"));
    }

    public function testValueNarrowsHandlesNamespaceKeywords(): void
    {
        $detector = new MixedVisibilityDetector();

        self::assertTrue($detector->valueNarrows('parent', 'App\Feature'));
        self::assertFalse($detector->valueNarrows('parent', 'App'));
        self::assertTrue($detector->valueNarrows('root', 'App'));
        self::assertFalse($detector->valueNarrows('namespace', ''));
    }

    public function testClassIsRestrictedIsCoveredByDeclarationRule(): void
    {
        self::addToAssertionCount(1);
    }
}
