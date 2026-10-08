<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Comparison;

use Guard\Policy\Comparison\OutlineComparator;
use Guard\Policy\Comparison\SequenceMatcher;
use Guard\Policy\Comparison\SequenceResult;
use Guard\Policy\Definition\DeclaredHeading;
use Guard\Policy\Definition\DocumentConfig;
use Guard\Policy\Definition\OutlineEntry;
use Guard\Policy\Diagnostic\DocumentViolationFactory;
use Guard\Policy\Diagnostic\HeadingViolation;
use Guard\Structure\Markdown\Heading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Comparison\OutlineComparator
 * @uses \Guard\Policy\Definition\DeclaredHeading
 * @uses \Guard\Policy\Definition\DocumentConfig
 * @uses \Guard\Policy\Definition\OutlineEntry
 * @uses \Guard\Policy\Comparison\SequenceMatcher
 * @uses \Guard\Policy\Comparison\SequenceResult
 * @uses \Guard\Policy\Diagnostic\DocumentViolationFactory
 * @uses \Guard\Policy\Diagnostic\HeadingViolation
 * @uses \Guard\Structure\Markdown\Heading
 */
#[CoversClass(OutlineComparator::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(OutlineEntry::class)]
#[UsesClass(SequenceMatcher::class)]
#[UsesClass(SequenceResult::class)]
#[UsesClass(DocumentViolationFactory::class)]
#[UsesClass(HeadingViolation::class)]
#[UsesClass(Heading::class)]
#[UsesClass(DeclaredHeading::class)]
final class OutlineComparatorTest extends TestCase
{
    /**
     * @dataProvider providerReadmes
     * @param list<Heading> $actual
     * @param list<string> $expected the rule and expected heading of each violation
     */
    #[DataProvider('providerReadmes')]
    public function testCompareReportsOnlyTheClosestOutline(array $actual, array $expected): void
    {
        $document = new DocumentConfig('README.md', null, 6, [
            'package' => [new OutlineEntry(1, '*'), new OutlineEntry(2, 'Requirements'), new OutlineEntry(2, 'Getting Started'), new OutlineEntry(2, '*', true, true), new OutlineEntry(2, 'License')],
            'monorepo' => [new OutlineEntry(1, '*'), new OutlineEntry(2, 'Packages'), new OutlineEntry(2, 'Related Projects', true), new OutlineEntry(2, 'License')],
        ]);

        $violations = (new OutlineComparator())->compare($document, $actual, 'guard.yaml');

        self::assertSame($expected, array_map(static fn (HeadingViolation $violation): string => $violation->rule . ' ' . $violation->expected, $violations));
    }

    /**
     * @return iterable<string, array{list<Heading>, list<string>}>
     */
    public static function providerReadmes(): iterable
    {
        yield 'monorepo root with deeper headings' => [[new Heading(1, 'Tool', 1), new Heading(2, 'Packages', 5), new Heading(3, 'Detail', 7), new Heading(2, 'License', 9)], []];
        yield 'package with installation instead of getting started' => [[new Heading(1, 'Tool', 1), new Heading(2, 'Requirements', 3), new Heading(2, 'Installation', 6), new Heading(2, 'License', 9)], ['missing_outline_heading ## Getting Started']];
        yield 'package with a section after license' => [[new Heading(1, 'Tool', 1), new Heading(2, 'Requirements', 3), new Heading(2, 'Getting Started', 6), new Heading(2, 'License', 9), new Heading(2, 'Acknowledgements', 12)], ['unexpected_outline_heading ']];
    }

    public function testCompareNamesTheClosestOutlineAndThePosition(): void
    {
        $document = new DocumentConfig('README.md', null, 6, [
            'package' => [new OutlineEntry(1, '*'), new OutlineEntry(2, 'Requirements'), new OutlineEntry(2, 'Getting Started'), new OutlineEntry(2, 'License')],
            'monorepo' => [new OutlineEntry(1, '*'), new OutlineEntry(2, 'Packages'), new OutlineEntry(2, 'License')],
        ]);

        $violations = (new OutlineComparator())->compare($document, [new Heading(1, 'Tool', 1), new Heading(2, 'Requirements', 3), new Heading(2, 'License', 9)], 'guard.yaml');

        self::assertCount(1, $violations);
        self::assertStringContainsString('outline "package" (the closest of the declared outlines package, monorepo)', $violations[0]->message);
        self::assertStringContainsString('declares it after "## Requirements"', $violations[0]->message);
    }

    public function testCompareReportsANamedHeadingOutOfOrder(): void
    {
        $document = new DocumentConfig('AGENTS.md', null, 6, ['agents' => [
            new OutlineEntry(1, 'AGENTS'),
            new OutlineEntry(2, 'Project Tradeoff Sliders', true),
            new OutlineEntry(2, 'Supported Versions'),
            new OutlineEntry(2, '*', true, true),
        ]]);
        $actual = [new Heading(1, 'AGENTS', 1), new Heading(2, 'Supported Versions', 3), new Heading(2, 'Project Tradeoff Sliders', 8)];

        $violations = (new OutlineComparator())->compare($document, $actual, 'guard.yaml');

        self::assertCount(1, $violations);
        self::assertSame('unexpected_outline_heading', $violations[0]->rule);
        self::assertSame(8, $violations[0]->line);
        self::assertSame('any other "##" heading', $violations[0]->expected);
        self::assertSame('Heading "## Project Tradeoff Sliders" does not follow outline "agents" declared for AGENTS.md in guard.yaml; expected any other "##" heading at that position. Rename, move, or remove the heading so the document follows the outline; changing the outline requires a human to update guard.yaml.', $violations[0]->message);
    }

    public function testCompareBreaksATieTowardTheOutlineWhoseSectionsTheDocumentUses(): void
    {
        $document = new DocumentConfig('README.md', null, 6, [
            'cli' => [new OutlineEntry(1, '*'), new OutlineEntry(2, 'Requirements'), new OutlineEntry(2, 'Getting Started'), new OutlineEntry(2, '*', true, true), new OutlineEntry(2, 'License')],
            'library' => [new OutlineEntry(1, '*'), new OutlineEntry(2, 'Requirements'), new OutlineEntry(2, 'Installation'), new OutlineEntry(2, 'Usage'), new OutlineEntry(2, '*', true, true), new OutlineEntry(2, 'License')],
        ]);
        $actual = [new Heading(1, 'SQL Parser', 1), new Heading(2, 'Requirements', 3), new Heading(2, 'Support Syntax', 5), new Heading(2, 'Installation', 7), new Heading(2, 'Usage', 9), new Heading(2, 'License', 11)];

        $violations = (new OutlineComparator())->compare($document, $actual, 'guard.yaml');

        self::assertCount(1, $violations);
        self::assertSame('## Support Syntax', $violations[0]->actual);
        self::assertStringContainsString('outline "library"', $violations[0]->message);
    }

    public function testNamedCountsHeadingsThatANamedEntryAccepts(): void
    {
        $entries = [new OutlineEntry(1, '*'), new OutlineEntry(2, 'Installation'), new OutlineEntry(2, 'Usage'), new OutlineEntry(2, '*', true, true)];

        self::assertSame(2, (new OutlineComparator())->named($entries, [new Heading(1, 'Tool', 1), new Heading(2, 'Usage', 3), new Heading(2, 'Installation', 5), new Heading(2, 'Notes', 7)]));
    }

    public function testVisibleKeepsHeadingsDownToTheDeepestEntry(): void
    {
        $visible = (new OutlineComparator())->visible([new OutlineEntry(1, '*')], [new Heading(1, 'Tool', 1), new Heading(2, 'Usage', 3)]);

        self::assertCount(1, $visible);
        self::assertSame('Tool', $visible[0]->text);
    }

    public function testAcceptsExcludesNamedTextsFromTheWildcard(): void
    {
        $matrix = (new OutlineComparator())->accepts(
            [new OutlineEntry(2, '*', true, true), new OutlineEntry(2, 'License')],
            [new Heading(2, 'Usage', 1), new Heading(2, 'License', 2), new Heading(3, 'Usage', 3)],
        );

        self::assertSame([[true, false], [false, true], [false, false]], $matrix);
    }

    public function testViolationsDescribeAMissingFirstHeading(): void
    {
        $violations = (new OutlineComparator())->violations('README.md', 'only', ['only'], [new OutlineEntry(1, 'Tool')], [], new SequenceResult([], [0]), 'guard.yaml');

        self::assertSame('Heading "# Tool" required by outline "only" is missing from README.md; guard.yaml declares it as the first heading. Add the section at that position; changing the outline requires a human to update guard.yaml.', $violations[0]->message);
    }

    public function testPositionNamesTheNearestNamedEntry(): void
    {
        $entries = [new OutlineEntry(1, '*'), new OutlineEntry(2, 'Packages'), new OutlineEntry(2, '*', true, true), new OutlineEntry(2, 'License')];
        $comparator = new OutlineComparator();

        self::assertSame('as the first heading', $comparator->position($entries, 0));
        self::assertSame('after the first level-1 heading', $comparator->position($entries, 1));
        self::assertSame('after "## Packages"', $comparator->position($entries, 3));
    }

    public function testCompareAcceptsAnyAlternativeAndKeepsDeeperHeadingsFree(): void
    {
        $document = new DocumentConfig('AGENTS.md', null, 6, ['agents' => [
            new OutlineEntry(1, 'AGENTS'),
            new OutlineEntry(2, 'Vision', false, false, [new DeclaredHeading(2, 'Product Vision')]),
            new OutlineEntry(2, 'Supported Versions'),
            new OutlineEntry(2, 'Architecture', true),
            new OutlineEntry(2, 'Documents'),
        ]]);
        $comparator = new OutlineComparator();

        self::assertSame([], $comparator->compare($document, [new Heading(1, 'AGENTS', 1), new Heading(2, 'Product Vision', 3), new Heading(2, 'Supported Versions', 5), new Heading(2, 'Architecture', 7), new Heading(3, 'Engines', 9), new Heading(2, 'Documents', 11)], 'guard.yaml'));
        $violations = $comparator->compare($document, [new Heading(1, 'AGENTS', 1), new Heading(2, 'Supported Versions', 5), new Heading(2, 'Documents', 11)], 'guard.yaml');
        self::assertSame(['## Vision or ## Product Vision'], array_map(static fn (HeadingViolation $violation): ?string => $violation->expected, $violations));
    }

    public function testDescribeQuotesNamedHeadings(): void
    {
        self::assertSame('"## License"', (new OutlineComparator())->describe(new OutlineEntry(2, 'License')));
        self::assertSame('"## Vision" or "## Product Vision"', (new OutlineComparator())->describe(new OutlineEntry(2, 'Vision', false, false, [new DeclaredHeading(2, 'Product Vision')])));
        self::assertSame('any other "###" heading', (new OutlineComparator())->describe(new OutlineEntry(3, '*')));
    }
}
