<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Guard\Config\Value\BadgeEntry;
use Guard\Reporting\DocumentViolationFactory;
use Guard\Reporting\HeadingViolation;
use Guard\Structure\Markdown\Badge\Badge;
use Guard\Structure\Markdown\Heading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Reporting\DocumentViolationFactory
 * @uses \Guard\Config\Value\BadgeEntry
 * @uses \Guard\Reporting\HeadingViolation
 * @uses \Guard\Structure\Markdown\Badge\Badge
 * @uses \Guard\Structure\Markdown\Heading
 */
#[CoversClass(DocumentViolationFactory::class)]
#[UsesClass(BadgeEntry::class)]
#[UsesClass(HeadingViolation::class)]
#[UsesClass(Badge::class)]
#[UsesClass(Heading::class)]
final class DocumentViolationFactoryTest extends TestCase
{
    public function testUnexpectedOutlineHeadingNamesTheAllowedHeadings(): void
    {
        $violation = (new DocumentViolationFactory())->unexpectedOutlineHeading('README.md', new Heading(2, 'Features', 7), ['"## Getting Started"'], 'package', ['package', 'monorepo'], 'guard.yaml');

        self::assertSame('unexpected_outline_heading', $violation->rule);
        self::assertSame(7, $violation->line);
        self::assertSame('"## Getting Started"', $violation->expected);
        self::assertSame('## Features', $violation->actual);
        self::assertSame('Heading "## Features" does not follow outline "package" (the closest of the declared outlines package, monorepo) declared for README.md in guard.yaml; expected "## Getting Started" at that position. Rename, move, or remove the heading so the document follows the outline; changing the outline requires a human to update guard.yaml.', $violation->message);
    }

    public function testUnexpectedOutlineHeadingAfterTheEndOfTheOutline(): void
    {
        $violation = (new DocumentViolationFactory())->unexpectedOutlineHeading('README.md', new Heading(2, 'Acknowledgements', 9), [], 'package', ['package'], 'guard.yaml');

        self::assertNull($violation->expected);
        self::assertStringContainsString('declared for README.md in guard.yaml; no further heading is allowed at that position.', $violation->message);
    }

    public function testMissingOutlineHeadingNamesItsPosition(): void
    {
        $violation = (new DocumentViolationFactory())->missingOutlineHeading('AGENTS.md', ['## Vision', '## Product Vision'], 'after "# AGENTS"', 'agents', ['agents'], 'guard.yaml');

        self::assertSame('missing_outline_heading', $violation->rule);
        self::assertNull($violation->line);
        self::assertSame('## Vision or ## Product Vision', $violation->expected);
        self::assertSame('Heading "## Vision" or "## Product Vision" required by outline "agents" is missing from AGENTS.md; guard.yaml declares it after "# AGENTS". Add the section at that position; changing the outline requires a human to update guard.yaml.', $violation->message);
    }

    public function testMissingBadgesExplainsEachCause(): void
    {
        $factory = new DocumentViolationFactory();
        $declared = [new BadgeEntry('workflow', 'w', null, true, true), new BadgeEntry('PHP', 'p')];

        $untitled = $factory->missingBadges('README.md', null, 2, $declared, 'guard.yaml');
        $early = $factory->missingBadges('README.md', new Heading(1, 'Tool', 3), 1, $declared, 'guard.yaml');
        $absent = $factory->missingBadges('README.md', new Heading(1, 'Tool', 1), null, $declared, 'guard.yaml');

        self::assertSame(2, $untitled->line);
        self::assertStringStartsWith('README.md has no level-1 title, so it has no badge block below one.', $untitled->message);
        self::assertSame(3, $early->line);
        self::assertStringStartsWith('The badges of README.md start on line 1, above the title "# Tool".', $early->message);
        self::assertSame('missing_badges', $absent->rule);
        self::assertSame('README.md has no badge block directly below its title "# Tool". Place the badges on the lines after the "# " title, separated from it by one blank line, in the order guard.yaml declares: workflow (optional, repeatable), PHP; changing the badges requires a human to update guard.yaml.', $absent->message);
    }

    public function testUnexpectedBadgeDistinguishesUndeclaredFromMisplaced(): void
    {
        $factory = new DocumentViolationFactory();
        $declared = [new BadgeEntry('PHP', 'https://img.shields.io/badge/php-*', 'PHP')];
        $badge = new Badge('npm', 'https://img.shields.io/npm/v/x', 'https://npmjs.com', 4);

        $undeclared = $factory->unexpectedBadge('README.md', $badge, null, $declared, 'guard.yaml');
        $misplaced = $factory->unexpectedBadge('README.md', $badge, $declared[0], $declared, 'guard.yaml');

        self::assertSame('Badge "npm" (https://img.shields.io/npm/v/x) is not declared for README.md in guard.yaml; guard.yaml declares the badge order PHP. Remove the badge or replace it with a declared one; allowing another badge requires a human to update guard.yaml.', $undeclared->message);
        self::assertSame('Badge "npm" is the declared "PHP" badge but is out of order or repeated in README.md; guard.yaml declares the badge order PHP. Move the badge to its declared position and keep one of each; allowing another badge requires a human to update guard.yaml.', $misplaced->message);
        self::assertSame(4, $misplaced->line);
    }

    public function testMissingBadgeNamesItsPatternAndPosition(): void
    {
        $factory = new DocumentViolationFactory();

        $first = $factory->missingBadge('README.md', new BadgeEntry('PHP', 'https://img.shields.io/badge/php-*', 'PHP'), 'as the first badge', 'guard.yaml');
        $later = $factory->missingBadge('README.md', new BadgeEntry('License', 'https://img.shields.io/badge/license-*'), 'after the "PHP" badge', 'guard.yaml');

        self::assertSame('Required badge "PHP" is missing from the badge block of README.md. Add a badge whose image URL matches "https://img.shields.io/badge/php-*" with the text "PHP" as the first badge; removing a required badge requires a human to update guard.yaml.', $first->message);
        self::assertStringContainsString('matches "https://img.shields.io/badge/license-*" after the "PHP" badge;', $later->message);
    }

    public function testUnexpectedContentQuotesTheExpectedText(): void
    {
        $violation = (new DocumentViolationFactory())->unexpectedContent('CLAUDE.md', '@AGENTS.md', 'guard.yaml');

        self::assertSame('unexpected_content', $violation->rule);
        self::assertSame('CLAUDE.md must contain exactly "@AGENTS.md" and nothing else, including no trailing newline, as declared in guard.yaml. Replace the whole file content with that text; changing the content requires a human to update guard.yaml.', $violation->message);
    }

    public function testOrderMarksOptionalAndRepeatableBadges(): void
    {
        self::assertSame('a, b (optional), c (repeatable)', (new DocumentViolationFactory())->order([new BadgeEntry('a', 'x'), new BadgeEntry('b', 'x', null, true), new BadgeEntry('c', 'x', null, false, true)]));
    }

    public function testAlternativesAreOnlyNamedWhenThereAreSeveral(): void
    {
        self::assertSame('', (new DocumentViolationFactory())->alternatives(['agents']));
        self::assertSame(' (the closest of the declared outlines a, b)', (new DocumentViolationFactory())->alternatives(['a', 'b']));
    }
}
