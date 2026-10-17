<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use ErrorException;
use Guard\Policy\FileChange;
use Guard\Reporting\ChangeDiff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Reporting\ChangeDiff
 * @uses \Guard\Policy\FileChange
 */
#[CoversClass(ChangeDiff::class)]
#[UsesClass(FileChange::class)]
final class ChangeDiffTest extends TestCase
{
    public function testRenderKeepsContextAndMarksMissingFinalNewlines(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);

        try {
            $diff = (new ChangeDiff())->render(new FileChange('a.txt', "keep\nold", "keep\nnew\n"));
            self::assertSame("--- a/a.txt\n+++ b/a.txt\n@@ -1,2 +1,2 @@\n keep\n-old\n\\ No newline at end of file\n+new\n", $diff);
            self::assertSame('', (new ChangeDiff())->render(new FileChange('a', '', '')));
        } finally {
            restore_error_handler();
        }
    }

    public function testLinesPreservesEmptyLines(): void
    {
        self::assertSame(["\n", "x\n"], (new ChangeDiff())->lines("\nx\n"));
        self::assertSame([], (new ChangeDiff())->lines(''));
    }

    public function testTerminatedLinesKeepsTheFinalUnterminatedLine(): void
    {
        self::assertSame(["a\n", 'b'], (new ChangeDiff())->terminatedLines("a\nb"));
    }

    public function testAppendMarksAnUnterminatedAddition(): void
    {
        $lines = [];
        (new ChangeDiff())->append($lines, ['value'], '+');
        self::assertSame(['+value', '\\ No newline at end of file'], $lines);
    }

}
