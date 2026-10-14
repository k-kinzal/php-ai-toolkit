<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Comparison;

use ErrorException;
use Guard\Policy\Comparison\HeadingSequenceAligner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Comparison\HeadingSequenceAligner
 */
#[CoversClass(HeadingSequenceAligner::class)]
final class HeadingSequenceAlignerTest extends TestCase
{
    /**
     * @dataProvider providerAlignments
     * @param list<string> $expected
     * @param list<string> $actual
     * @param list<array{0: ?int, 1: ?int}> $steps
     */
    #[DataProvider('providerAlignments')]
    public function testAlignWalksBothSequencesWithoutReadingOutsideItsTable(array $expected, array $actual, array $steps): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);

        try {
            self::assertSame($steps, (new HeadingSequenceAligner())->align($expected, $actual));
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @return iterable<string, array{list<string>, list<string>, list<array{0: ?int, 1: ?int}>}>
     */
    public static function providerAlignments(): iterable
    {
        yield 'identical sequences' => [['# A', '## B'], ['# A', '## B'], [[0, 0], [1, 1]]];
        yield 'added and dropped headings' => [
            ['# A', '## B', '## C'],
            ['# A', '## New', '## B'],
            [[0, 0], [null, 1], [1, 2], [2, null]],
        ];
        yield 'both empty' => [[], [], []];
        yield 'only actual headings' => [[], ['# A'], [[null, 0]]];
        yield 'only expected headings' => [['# A'], [], [[0, null]]];
        yield 'trailing added headings' => [['# A'], ['# A', '## B', '## C'], [[0, 0], [null, 1], [null, 2]]];
        yield 'trailing dropped headings' => [['# A', '## B', '## C'], ['# A'], [[0, 0], [1, null], [2, null]]];
        yield 'longest common subsequence' => [
            ['# A', '## B', '## C', '## D'],
            ['# A', '## C', '## B', '## D'],
            [[0, 0], [1, null], [2, 1], [null, 2], [3, 3]],
        ];
    }
}
