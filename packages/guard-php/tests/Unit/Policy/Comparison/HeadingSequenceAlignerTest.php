<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Comparison;

use ErrorException;
use Guard\Policy\Comparison\HeadingSequenceAligner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Comparison\HeadingSequenceAligner
 */
#[CoversClass(HeadingSequenceAligner::class)]
final class HeadingSequenceAlignerTest extends TestCase
{
    public function testAlignMatchesIdenticalSequences(): void
    {
        self::assertSame([[0, 0], [1, 1]], (new HeadingSequenceAligner())->align(['# A', '## B'], ['# A', '## B']));
    }

    public function testAlignReportsAddedAndDroppedHeadings(): void
    {
        self::assertSame(
            [[0, 0], [null, 1], [1, 2], [2, null]],
            (new HeadingSequenceAligner())->align(['# A', '## B', '## C'], ['# A', '## New', '## B']),
        );
    }

    public function testAlignHandlesEmptySequences(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);

        try {
            self::assertSame([], (new HeadingSequenceAligner())->align([], []));
            self::assertSame([[null, 0]], (new HeadingSequenceAligner())->align([], ['# A']));
            self::assertSame([[0, null]], (new HeadingSequenceAligner())->align(['# A'], []));
        } finally {
            restore_error_handler();
        }
    }

    public function testAlignKeepsTheLongestCommonSubsequence(): void
    {
        self::assertSame(
            [[0, 0], [1, null], [2, 1], [null, 2], [3, 3]],
            (new HeadingSequenceAligner())->align(['# A', '## B', '## C', '## D'], ['# A', '## C', '## B', '## D']),
        );
    }
}
