<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Comparison;

use Guard\Policy\Comparison\HeadingHunkClassifier;
use Guard\Policy\Comparison\HeadingSequenceAligner;
use Guard\Policy\Comparison\HeadingStructureComparator;
use Guard\Policy\Definition\DeclaredHeading;
use Guard\Policy\Definition\DocumentConfig;
use Guard\Policy\Diagnostic\HeadingViolation;
use Guard\Policy\Diagnostic\HeadingViolationFactory;
use Guard\Structure\Markdown\Heading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Comparison\HeadingStructureComparator
 * @uses \Guard\Policy\Definition\DeclaredHeading
 * @uses \Guard\Policy\Definition\DocumentConfig
 * @uses \Guard\Policy\Comparison\HeadingHunkClassifier
 * @uses \Guard\Policy\Comparison\HeadingSequenceAligner
 * @uses \Guard\Policy\Diagnostic\HeadingViolation
 * @uses \Guard\Policy\Diagnostic\HeadingViolationFactory
 * @uses \Guard\Structure\Markdown\Heading
 */
#[CoversClass(HeadingStructureComparator::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(HeadingHunkClassifier::class)]
#[UsesClass(HeadingSequenceAligner::class)]
#[UsesClass(HeadingViolation::class)]
#[UsesClass(HeadingViolationFactory::class)]
#[UsesClass(Heading::class)]
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
            array_map(static fn (HeadingViolation $violation): array => [$violation->rule, $violation->line], $violations),
        );
    }

    public function testCompareReportsMovedSections(): void
    {
        $declared = [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Requirements'), new DeclaredHeading(2, 'Usage'), new DeclaredHeading(2, 'License')];
        $actual = [new Heading(1, 'Tool', 1), new Heading(2, 'Usage', 3), new Heading(2, 'Requirements', 5), new Heading(2, 'License', 7)];

        $violations = (new HeadingStructureComparator())->compare(new DocumentConfig('README.md', $declared, 6), $actual, 'doc-guard.yaml');

        self::assertSame(
            [['moved_heading', '## Requirements', 5]],
            array_map(static fn (HeadingViolation $violation): array => [$violation->rule, $violation->actual, $violation->line], $violations),
        );
    }

    public function testCompareReportsRemovedAndRenamedSections(): void
    {
        $declared = [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Usage'), new DeclaredHeading(2, 'Limitations')];
        $actual = [new Heading(1, 'Tool', 1), new Heading(2, 'Getting Started', 3)];

        $violations = (new HeadingStructureComparator())->compare(new DocumentConfig('README.md', $declared, 6), $actual, 'doc-guard.yaml');

        self::assertSame(
            [['unexpected_heading', '## Getting Started'], ['missing_heading', '## Usage'], ['missing_heading', '## Limitations']],
            array_map(static fn (HeadingViolation $violation): array => [$violation->rule, $violation->actual ?? $violation->expected], $violations),
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
