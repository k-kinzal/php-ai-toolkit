<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\BadgeConfigReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\BadgeEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\BadgeConfigReader
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Policy\Definition\BadgeEntry
 * @uses \Guard\Diagnostic\PolicyException
 */
#[CoversClass(BadgeConfigReader::class)]
#[UsesClass(HeadingConfigKeyValidator::class)]
#[UsesClass(BadgeEntry::class)]
#[UsesClass(PolicyException::class)]
final class BadgeConfigReaderTest extends TestCase
{
    public function testReadKeepsOrderAndDefaultsFlagsToRequiredOnce(): void
    {
        $badges = (new BadgeConfigReader())->read([
            ['name' => 'workflow', 'image' => 'https://github.com/*', 'optional' => true, 'repeat' => true],
            ['name' => 'PHP', 'image' => 'https://img.shields.io/badge/php-*', 'label' => 'PHP'],
        ], 'documents.README.md');

        self::assertSame(['workflow', 'PHP'], array_map(static fn (BadgeEntry $badge): string => $badge->name, $badges));
        self::assertTrue($badges[0]->repeat);
        self::assertNull($badges[0]->label);
        self::assertFalse($badges[1]->optional);
        self::assertSame('PHP', $badges[1]->label);
    }

    public function testReadRejectsAnEmptyList(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md.badges" must be a non-empty list of badges');

        (new BadgeConfigReader())->read([], 'documents.README.md');
    }

    public function testReadRejectsADuplicateName(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('declares the badge "PHP" more than once. Use "repeat: true"');

        (new BadgeConfigReader())->read([['name' => 'PHP', 'image' => 'a'], ['name' => 'PHP', 'image' => 'b']], 'documents.README.md');
    }

    public function testEntryRejectsAList(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"entry" must be a mapping with "name" and "image".');

        (new BadgeConfigReader())->entry(['PHP'], 'entry');
    }

    public function testEntryRequiresNameAndImage(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"entry.name" and "entry.image" must be non-empty strings.');

        (new BadgeConfigReader())->entry(['name' => 'PHP'], 'entry');
    }

    public function testEntryRejectsANonStringLabel(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"entry.label" must be a string.');

        (new BadgeConfigReader())->entry(['name' => 'PHP', 'image' => 'a', 'label' => 1], 'entry');
    }

    public function testEntryRejectsNonBooleanFlags(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"entry.optional" and "entry.repeat" must be true or false.');

        (new BadgeConfigReader())->entry(['name' => 'PHP', 'image' => 'a', 'repeat' => 1], 'entry');
    }

    public function testEntryRejectsUnknownKeys(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"entry" contains unsupported key "link"');

        (new BadgeConfigReader())->entry(['name' => 'PHP', 'image' => 'a', 'link' => 'b'], 'entry');
    }
}
