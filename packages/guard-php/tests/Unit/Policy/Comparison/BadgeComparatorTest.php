<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Comparison;

use Guard\Config\Value\BadgeEntry;
use Guard\Policy\Comparison\BadgeComparator;
use Guard\Policy\Comparison\SequenceMatcher;
use Guard\Policy\Comparison\SequenceResult;
use Guard\Reporting\DocumentViolationFactory;
use Guard\Reporting\HeadingViolation;
use Guard\Structure\Markdown\Badge\Badge;
use Guard\Structure\Markdown\Badge\BadgeBlock;
use Guard\Structure\Markdown\Heading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Comparison\BadgeComparator
 * @uses \Guard\Config\Value\BadgeEntry
 * @uses \Guard\Policy\Comparison\SequenceMatcher
 * @uses \Guard\Policy\Comparison\SequenceResult
 * @uses \Guard\Reporting\DocumentViolationFactory
 * @uses \Guard\Reporting\HeadingViolation
 * @uses \Guard\Structure\Markdown\Badge\Badge
 * @uses \Guard\Structure\Markdown\Badge\BadgeBlock
 * @uses \Guard\Structure\Markdown\Heading
 */
#[CoversClass(BadgeComparator::class)]
#[UsesClass(BadgeEntry::class)]
#[UsesClass(SequenceMatcher::class)]
#[UsesClass(SequenceResult::class)]
#[UsesClass(DocumentViolationFactory::class)]
#[UsesClass(HeadingViolation::class)]
#[UsesClass(Badge::class)]
#[UsesClass(BadgeBlock::class)]
#[UsesClass(Heading::class)]
final class BadgeComparatorTest extends TestCase
{
    public function testCompareAcceptsTheDeclaredOrder(): void
    {
        $declared = [
            new BadgeEntry('workflow', 'https://github.com/*/actions/workflows/*', null, true, true),
            new BadgeEntry('PHP', 'https://img.shields.io/badge/php-*', 'PHP'),
            new BadgeEntry('License', 'https://img.shields.io/badge/license-*', 'License'),
        ];
        $block = new BadgeBlock(new Heading(1, 'Tool', 1), [
            new Badge('CI', 'https://github.com/o/r/actions/workflows/ci.yml/badge.svg', 'https://example.com/', 3),
            new Badge('PHP', 'https://img.shields.io/badge/php-8.1', 'https://www.php.net/', 4),
            new Badge('License', 'https://img.shields.io/badge/license-MIT', 'LICENSE', 5),
        ], null);

        self::assertSame([], (new BadgeComparator())->compare('README.md', $declared, $block, 'guard.yaml'));
    }

    public function testCompareReportsOrderUnknownBadgesAndMissingRequiredOnes(): void
    {
        $declared = [
            new BadgeEntry('workflow', 'https://github.com/*/actions/workflows/*', null, true, true),
            new BadgeEntry('PHP', 'https://img.shields.io/badge/php-*', 'PHP'),
            new BadgeEntry('License', 'https://img.shields.io/badge/license-*', 'License'),
        ];
        $block = new BadgeBlock(new Heading(1, 'Tool', 1), [
            new Badge('License', 'https://img.shields.io/badge/license-MIT', 'LICENSE', 3),
            new Badge('PHP', 'https://img.shields.io/badge/php-8.1', 'https://www.php.net/', 4),
            new Badge('npm', 'https://img.shields.io/npm/v/x', 'https://www.npmjs.com/', 5),
        ], null);

        $violations = (new BadgeComparator())->compare('README.md', $declared, $block, 'guard.yaml');

        self::assertSame(['unexpected_badge', 'unexpected_badge', 'missing_badge'], array_map(static fn (HeadingViolation $violation): string => $violation->rule, $violations));
        self::assertSame(4, $violations[0]->line);
        self::assertStringContainsString('Badge "PHP" is the declared "PHP" badge but is out of order or repeated in README.md', $violations[0]->message);
        self::assertStringContainsString('Badge "npm" (https://img.shields.io/npm/v/x) is not declared for README.md in guard.yaml', $violations[1]->message);
        self::assertStringContainsString('Required badge "PHP" is missing', $violations[2]->message);
        self::assertStringContainsString('after any workflow badges', $violations[2]->message);
    }

    public function testPositionNamesTheNearestRequiredBadgeOrTheOptionalOnesBefore(): void
    {
        $declared = [
            new BadgeEntry('docs', 'd', null, true),
            new BadgeEntry('workflow', 'w', null, true, true),
            new BadgeEntry('PHP', 'p'),
            new BadgeEntry('Packagist', 'k', null, true),
            new BadgeEntry('License', 'l'),
        ];
        $comparator = new BadgeComparator();

        self::assertSame('as the first badge', $comparator->position($declared, 0));
        self::assertSame('after any docs, workflow badges', $comparator->position($declared, 2));
        self::assertSame('after the "PHP" badge', $comparator->position($declared, 4));
    }

    public function testCompareReportsAMissingBlockOnce(): void
    {
        $declared = [new BadgeEntry('PHP', 'https://img.shields.io/badge/php-*'), new BadgeEntry('License', 'https://img.shields.io/badge/license-*')];

        $violations = (new BadgeComparator())->compare('README.md', $declared, new BadgeBlock(new Heading(1, 'Tool', 1), [], null), 'guard.yaml');

        self::assertCount(1, $violations);
        self::assertSame('missing_badges', $violations[0]->rule);
    }

    public function testDeclarationReturnsTheFirstMatchingEntry(): void
    {
        $declared = [new BadgeEntry('any', 'https://img.shields.io/*'), new BadgeEntry('PHP', 'https://img.shields.io/badge/php-*')];
        $comparator = new BadgeComparator();

        self::assertSame('any', $comparator->declaration($declared, new Badge('PHP', 'https://img.shields.io/badge/php-8.1', 'https://www.php.net/', 1))?->name);
        self::assertNull($comparator->declaration($declared, new Badge('npm', 'https://badge.fury.io/js/x.svg', 'https://www.npmjs.com/', 1)));
    }
}
