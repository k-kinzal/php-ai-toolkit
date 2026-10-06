<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Doc;

use Guard\Collect\Markdown\Parsing\Heading;
use Guard\Config\Doc\DeclaredHeading;
use Guard\Config\Doc\DocumentConfig;
use Guard\Policy\Doc\HeadingHunkClassifier;
use Guard\Policy\Doc\HeadingSequenceAligner;
use Guard\Policy\Doc\HeadingStructureComparator;
use Guard\Policy\Doc\Violation;
use Guard\Policy\Doc\ViolationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Doc\HeadingStructureComparator
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Policy\Doc\HeadingHunkClassifier
 * @uses \Guard\Policy\Doc\HeadingSequenceAligner
 * @uses \Guard\Policy\Doc\Violation
 * @uses \Guard\Policy\Doc\ViolationFactory
 */
#[CoversClass(HeadingStructureComparator::class)]
#[UsesClass(Heading::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(HeadingHunkClassifier::class)]
#[UsesClass(HeadingSequenceAligner::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFactory::class)]
final class HeadingStructureComparatorTest extends TestCase
{
    public function testCompareAcceptsTheDeclaredStructure(): void
    {
        $document = new DocumentConfig('README.md', [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Usage')], 6);

        self::assertSame([], (new HeadingStructureComparator())->compare($document, [new Heading(1, 'Tool', 1), new Heading(2, 'Usage', 5)], 'doc-guard.yaml'));
    }

    public function testCompareReportsAddedSections(): void
    {
        $document = new DocumentConfig('README.md', [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'License')], 6);
        $actual = [new Heading(1, 'Tool', 1), new Heading(2, 'Development', 3), new Heading(3, 'Testing', 5), new Heading(2, 'License', 7)];

        $violations = (new HeadingStructureComparator())->compare($document, $actual, 'doc-guard.yaml');

        self::assertSame(
            [['unexpected_heading', 3], ['unexpected_heading', 5]],
            array_map(static fn (Violation $violation): array => [$violation->rule, $violation->line], $violations),
        );
    }

    public function testCompareReportsMovedSections(): void
    {
        $declared = [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Requirements'), new DeclaredHeading(2, 'Usage'), new DeclaredHeading(2, 'License')];
        $actual = [new Heading(1, 'Tool', 1), new Heading(2, 'Usage', 3), new Heading(2, 'Requirements', 5), new Heading(2, 'License', 7)];

        $violations = (new HeadingStructureComparator())->compare(new DocumentConfig('README.md', $declared, 6), $actual, 'doc-guard.yaml');

        self::assertSame(
            [['moved_heading', '## Requirements', 5]],
            array_map(static fn (Violation $violation): array => [$violation->rule, $violation->actual, $violation->line], $violations),
        );
    }

    public function testCompareReportsRemovedAndRenamedSections(): void
    {
        $declared = [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Usage'), new DeclaredHeading(2, 'Limitations')];
        $actual = [new Heading(1, 'Tool', 1), new Heading(2, 'Getting Started', 3)];

        $violations = (new HeadingStructureComparator())->compare(new DocumentConfig('README.md', $declared, 6), $actual, 'doc-guard.yaml');

        self::assertSame(
            [['unexpected_heading', '## Getting Started'], ['missing_heading', '## Usage'], ['missing_heading', '## Limitations']],
            array_map(static fn (Violation $violation): array => [$violation->rule, $violation->actual ?? $violation->expected], $violations),
        );
    }

    public function testHunksGroupsUnmatchedStepsBetweenMatches(): void
    {
        self::assertSame(
            [['removed' => [1], 'added' => [1, 2]], ['removed' => [], 'added' => [4]]],
            (new HeadingStructureComparator())->hunks([[0, 0], [1, null], [null, 1], [null, 2], [2, 3], [null, 4]]),
        );
        self::assertSame([], (new HeadingStructureComparator())->hunks([[0, 0]]));
    }
}
