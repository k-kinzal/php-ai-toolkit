<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Value;

use Guard\Config\Value\BadgeEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Value\BadgeEntry
 */
#[CoversClass(BadgeEntry::class)]
final class BadgeEntryTest extends TestCase
{
    public function testGetExposesTheDeclaration(): void
    {
        $entry = new BadgeEntry('PHP', 'https://img.shields.io/badge/php-*', 'PHP', true, false);

        self::assertSame('PHP', $entry->name);
        self::assertSame('https://img.shields.io/badge/php-*', $entry->image);
        self::assertSame('PHP', $entry->label);
        self::assertTrue($entry->optional);
        self::assertFalse($entry->repeat);
    }

    public function testAcceptsMatchesTheImageGlobAcrossSlashes(): void
    {
        $entry = new BadgeEntry('workflow', 'https://github.com/*/actions/workflows/*/badge.svg*');

        self::assertTrue($entry->accepts('CI', 'https://github.com/k-kinzal/peq/actions/workflows/ci.yml/badge.svg?branch=main'));
        self::assertFalse($entry->accepts('CI', 'https://img.shields.io/badge/ci-passing'));
    }

    public function testAcceptsRequiresTheDeclaredLabelExactly(): void
    {
        $entry = new BadgeEntry('License', 'https://img.shields.io/badge/license-*', 'License');

        self::assertTrue($entry->accepts('License', 'https://img.shields.io/badge/license-MIT-blue'));
        self::assertFalse($entry->accepts('License: MIT', 'https://img.shields.io/badge/license-MIT-blue'));
        self::assertFalse($entry->accepts('License', 'https://img.shields.io/badge/License-MIT-yellow.svg'));
    }
}
