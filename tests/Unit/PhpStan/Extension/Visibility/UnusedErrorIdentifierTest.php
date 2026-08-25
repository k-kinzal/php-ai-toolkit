<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\Visibility;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Extension\Visibility\UnusedErrorIdentifier;

/**
 * @covers \Toolkit\PhpStan\Extension\Visibility\UnusedErrorIdentifier
 */
#[CoversClass(UnusedErrorIdentifier::class)]
final class UnusedErrorIdentifierTest extends TestCase
{
    /**
     * @dataProvider providerUnusedIdentifier
     */
    #[DataProvider('providerUnusedIdentifier')]
    public function testMatchesUnusedRuleFamilies(string $identifier): void
    {
        self::assertTrue((new UnusedErrorIdentifier())->matches($identifier));
    }

    public function testMatchesRejectsAnUnrelatedError(): void
    {
        self::assertFalse((new UnusedErrorIdentifier())->matches('argument.type'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function providerUnusedIdentifier(): array
    {
        return [
            'PHPStan unused method' => ['method.unused'],
            'PHPStan write-only property' => ['property.onlyWritten'],
            'ShipMonk dead method' => ['shipmonk.deadMethod'],
            'unused-public extension' => ['unusedPublic.method'],
        ];
    }
}
