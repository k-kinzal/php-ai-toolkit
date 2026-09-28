<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\HeadingHunkClassifier;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Analysis\ViolationFactory;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Markdown\Heading;

/**
 * @covers \Toolkit\DocGuard\Analysis\HeadingHunkClassifier
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Analysis\Violation
 * @uses \Toolkit\DocGuard\Analysis\ViolationFactory
 */
#[CoversClass(HeadingHunkClassifier::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(Heading::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFactory::class)]
final class HeadingHunkClassifierTest extends TestCase
{
    public function testClassifyReportsLevelChangesAndRenames(): void
    {
        $declared = [new DeclaredHeading(2, 'Usage'), new DeclaredHeading(3, 'Basic')];
        $actual = [new Heading(2, 'Getting Started', 3), new Heading(4, 'Basic', 5)];

        $violations = (new HeadingHunkClassifier())->classify('README.md', $declared, $actual, [0, 1], [0, 1], 'doc-guard.yaml');

        self::assertSame(
            [['changed_heading_level', '### Basic', '#### Basic'], ['renamed_heading', '## Usage', '## Getting Started']],
            array_map(static fn (Violation $violation): array => [$violation->rule, $violation->expected, $violation->actual], $violations),
        );
    }

    public function testClassifyReportsAdditionsAndRemovalsWhenHeadingsDoNotPairUp(): void
    {
        $declared = [new DeclaredHeading(2, 'Limitations')];
        $actual = [new Heading(2, 'Development', 3), new Heading(3, 'Testing', 5)];

        $violations = (new HeadingHunkClassifier())->classify('README.md', $declared, $actual, [0], [0, 1], 'doc-guard.yaml');

        self::assertSame(
            [['unexpected_heading', '## Development'], ['unexpected_heading', '### Testing'], ['missing_heading', '## Limitations']],
            array_map(static fn (Violation $violation): array => [$violation->rule, $violation->actual ?? $violation->expected], $violations),
        );
    }

    public function testPairsAsRenamesRequiresEqualCountsAndLevels(): void
    {
        $declared = [new DeclaredHeading(2, 'A'), new DeclaredHeading(3, 'B')];
        $actual = [new Heading(2, 'X', 1), new Heading(2, 'Y', 2)];

        self::assertTrue((new HeadingHunkClassifier())->pairsAsRenames($declared, $actual, [0], [0]));
        self::assertFalse((new HeadingHunkClassifier())->pairsAsRenames($declared, $actual, [0, 1], [0, 1]));
        self::assertFalse((new HeadingHunkClassifier())->pairsAsRenames($declared, $actual, [0], [0, 1]));
        self::assertFalse((new HeadingHunkClassifier())->pairsAsRenames($declared, $actual, [], []));
    }
}
