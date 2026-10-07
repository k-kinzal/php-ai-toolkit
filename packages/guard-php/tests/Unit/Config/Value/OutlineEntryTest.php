<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Value;

use Guard\Config\Value\DeclaredHeading;
use Guard\Config\Value\OutlineEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Value\OutlineEntry
 * @uses \Guard\Config\Value\DeclaredHeading
 */
#[CoversClass(OutlineEntry::class)]
#[UsesClass(DeclaredHeading::class)]
final class OutlineEntryTest extends TestCase
{
    public function testGetExposesLevelTextAndOccurrenceFlags(): void
    {
        $entry = new OutlineEntry(2, 'License', true, true);

        self::assertSame(2, $entry->level);
        self::assertSame('License', $entry->text);
        self::assertTrue($entry->optional);
        self::assertTrue($entry->repeat);
    }

    public function testWildcardIsOnlyTheBareAsterisk(): void
    {
        self::assertTrue((new OutlineEntry(2, '*'))->wildcard());
        self::assertFalse((new OutlineEntry(2, '* Notes'))->wildcard());
    }

    public function testHeadingsListsThePrimaryHeadingFirst(): void
    {
        $entry = new OutlineEntry(2, 'Vision', false, false, [new DeclaredHeading(2, 'Product Vision')]);

        self::assertCount(2, $entry->headings());
        self::assertSame('Vision', $entry->headings()[0]->text);
        self::assertCount(1, $entry->alternatives);
    }

    public function testNamesMatchesAnyAcceptedHeadingAtItsLevel(): void
    {
        $entry = new OutlineEntry(2, 'Vision', false, false, [new DeclaredHeading(2, 'Product Vision')]);

        self::assertTrue($entry->names(2, 'Product Vision'));
        self::assertFalse($entry->names(3, 'Vision'));
        self::assertFalse($entry->names(2, 'Overview'));
    }

    public function testNotationsListsEveryAcceptedHeading(): void
    {
        self::assertSame(['## Vision', '## Product Vision'], (new OutlineEntry(2, 'Vision', false, false, [new DeclaredHeading(2, 'Product Vision')]))->notations());
    }

    public function testNotationWritesTheHeadingInAtxForm(): void
    {
        self::assertSame('## *', (new OutlineEntry(2, '*'))->notation());
        self::assertSame('# AGENTS', (new OutlineEntry(1, 'AGENTS'))->notation());
    }
}
