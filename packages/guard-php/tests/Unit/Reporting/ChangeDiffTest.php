<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

/**
 * @covers \Guard\Reporting\ChangeDiff
 * @uses \Guard\Policy\FileChange
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Guard\Reporting\ChangeDiff::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
final class ChangeDiffTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderKeepsContextAndMarksMissingFinalNewlines(): void
    {
        $diff = (new \Guard\Reporting\ChangeDiff())->render(new \Guard\Policy\FileChange('a.txt', "keep\nold", "keep\nnew\n"));
        self::assertSame("--- a/a.txt\n+++ b/a.txt\n@@ -1,2 +1,2 @@\n keep\n-old\n\\ No newline at end of file\n+new\n", $diff);
        self::assertSame('', (new \Guard\Reporting\ChangeDiff())->render(new \Guard\Policy\FileChange('a', '', '')));
    }

    public function testLinesPreservesEmptyLines(): void
    {
        self::assertSame(["\n", "x\n"], (new \Guard\Reporting\ChangeDiff())->lines("\nx\n"));
        self::assertSame([], (new \Guard\Reporting\ChangeDiff())->lines(''));
    }

    public function testTerminatedLinesKeepsTheFinalUnterminatedLine(): void
    {
        self::assertSame(["a\n", 'b'], (new \Guard\Reporting\ChangeDiff())->terminatedLines("a\nb"));
    }

    public function testAppendMarksAnUnterminatedAddition(): void
    {
        $lines = [];
        (new \Guard\Reporting\ChangeDiff())->append($lines, ['value'], '+');
        self::assertSame(['+value', '\\ No newline at end of file'], $lines);
    }

}
