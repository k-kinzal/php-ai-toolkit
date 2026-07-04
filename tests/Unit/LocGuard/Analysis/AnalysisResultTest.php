<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Analysis\FileMetric;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnalysisResult::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(Violation::class)]
final class AnalysisResultTest extends TestCase
{
    public function testHasViolationsReturnsTrueWhenViolationsExist(): void
    {
        $result = new AnalysisResult(
            [new FileMetric('src/A.php', 10, 7)],
            [new Violation('src/A.php', 1, 'file_lines', 10, 5, 'Too long.')],
        );

        self::assertTrue($result->hasViolations());
    }

    public function testViolationCountReturnsViolationTotal(): void
    {
        $result = new AnalysisResult(
            [],
            [new Violation('src/A.php', 1, 'file_lines', 10, 5, 'Too long.')],
        );

        self::assertSame(1, $result->violationCount());
    }

    public function testFileCountReturnsAnalyzedFileTotal(): void
    {
        $result = new AnalysisResult([new FileMetric('src/A.php', 10, 7)], []);

        self::assertSame(1, $result->fileCount());
    }

    public function testPhysicalLineCountReturnsTotalPhysicalLines(): void
    {
        $result = new AnalysisResult([
            new FileMetric('src/A.php', 10, 7),
            new FileMetric('src/B.php', 3, 2),
        ], []);

        self::assertSame(13, $result->physicalLineCount());
    }

    public function testNonCommentLineCountReturnsTotalNcloc(): void
    {
        $result = new AnalysisResult([
            new FileMetric('src/A.php', 10, 7),
            new FileMetric('src/B.php', 3, 2),
        ], []);

        self::assertSame(9, $result->nonCommentLineCount());
    }
}
