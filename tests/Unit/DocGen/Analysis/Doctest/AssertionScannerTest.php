<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Doctest;

use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionLine;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \PhpAiToolkit\DocGen\Analysis\Doctest\AssertionLine
 */
#[CoversClass(AssertionScanner::class)]
#[UsesClass(AssertionLine::class)]
final class AssertionScannerTest extends TestCase
{
    public function testScanClassifiesEachLineAndPreservesIndentationInText(): void
    {
        $lines = (new AssertionScanner())->scan('  $sum = add(1, 2);' . "\n" . '  $sum // => 3');

        self::assertCount(2, $lines);
        self::assertSame('  $sum = add(1, 2);', $lines[0]->text);
        self::assertSame('$sum = add(1, 2);', $lines[0]->code);
        self::assertNull($lines[0]->marker);
        self::assertSame('  $sum // => 3', $lines[1]->text);
        self::assertSame('$sum', $lines[1]->code);
        self::assertSame('return', $lines[1]->marker);
        self::assertSame('3', $lines[1]->expected);
    }

    public function testScanLineDetectsReturnMarkerWithTrimmedExpectation(): void
    {
        $line = (new AssertionScanner())->scanLine('add(1, 2) // =>   3');

        self::assertSame('add(1, 2) // =>   3', $line->text);
        self::assertSame('add(1, 2)', $line->code);
        self::assertSame('return', $line->marker);
        self::assertSame('3', $line->expected);
        self::assertNull($line->exceptionMessage);
    }

    public function testScanLineDetectsOutputMarkerWithVerbatimExpectation(): void
    {
        $line = (new AssertionScanner())->scanLine('echo greet(); // Output: Hello,  World');

        self::assertSame('echo greet();', $line->code);
        self::assertSame('output', $line->marker);
        self::assertSame('Hello,  World', $line->expected);
        self::assertSame('', (new AssertionScanner())->scanLine('echo greet(); // Output:')->expected);
    }

    public function testScanLineDetectsThrowsMarkerWithMessage(): void
    {
        $line = (new AssertionScanner())->scanLine('$service->fail(); // throws RuntimeException: boom happened');

        self::assertSame('$service->fail();', $line->code);
        self::assertSame('throws', $line->marker);
        self::assertSame('RuntimeException', $line->expected);
        self::assertSame('boom happened', $line->exceptionMessage);
    }

    public function testScanLineDetectsThrowsMarkerWithoutMessage(): void
    {
        $line = (new AssertionScanner())->scanLine('$service->fail(); // throws LogicException');

        self::assertSame('throws', $line->marker);
        self::assertSame('LogicException', $line->expected);
        self::assertNull($line->exceptionMessage);
    }

    public function testScanLineTreatsPlainCodeAsSmokeLine(): void
    {
        $line = (new AssertionScanner())->scanLine('  $sum = 1 + 2;');

        self::assertSame('  $sum = 1 + 2;', $line->text);
        self::assertSame('$sum = 1 + 2;', $line->code);
        self::assertNull($line->marker);
        self::assertNull($line->expected);
        self::assertNull($line->exceptionMessage);
    }

    public function testScanLineTreatsCommentOnlyLineAsSmokeLine(): void
    {
        $line = (new AssertionScanner())->scanLine('// => 3');

        self::assertSame('// => 3', $line->text);
        self::assertSame('// => 3', $line->code);
        self::assertNull($line->marker);
        self::assertNull($line->expected);
    }
}
