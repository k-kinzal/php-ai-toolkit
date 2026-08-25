<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Parse\ParameterModifiers;

/**
 * @covers \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 */
#[CoversClass(ParameterModifiers::class)]
final class ParameterModifiersTest extends TestCase
{
    public function testPromotedVisibilityReturnsPublicForPublicFlag(): void
    {
        self::assertSame('public', (new ParameterModifiers())->promotedVisibility(ParameterModifiers::VISIBILITY_PUBLIC));
    }

    public function testPromotedVisibilityReturnsProtectedForProtectedFlag(): void
    {
        self::assertSame('protected', (new ParameterModifiers())->promotedVisibility(ParameterModifiers::VISIBILITY_PROTECTED));
    }

    public function testPromotedVisibilityReturnsPrivateForPrivateFlag(): void
    {
        self::assertSame('private', (new ParameterModifiers())->promotedVisibility(ParameterModifiers::VISIBILITY_PRIVATE));
    }

    public function testPromotedVisibilityReturnsNullWithoutVisibilityFlag(): void
    {
        self::assertNull((new ParameterModifiers())->promotedVisibility(0));
    }

    public function testPromotedVisibilityReturnsNullForReadonlyOnlyFlag(): void
    {
        self::assertNull((new ParameterModifiers())->promotedVisibility(ParameterModifiers::READONLY));
    }

    public function testPromotedVisibilityKeepsVisibilityCombinedWithReadonly(): void
    {
        self::assertSame('private', (new ParameterModifiers())->promotedVisibility(ParameterModifiers::READONLY | ParameterModifiers::VISIBILITY_PRIVATE));
    }
}
