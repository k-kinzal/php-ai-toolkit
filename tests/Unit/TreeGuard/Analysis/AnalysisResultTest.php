<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PhpAiToolkit\TreeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnalysisResult::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(Violation::class)]
final class AnalysisResultTest extends TestCase
{
    public function testHasViolationsReflectsViolationList(): void
    {
        $listing = new DirectoryListing('src', ['A.php'], []);
        $violation = new Violation('src', 'max_files', 'src', 1, 1, 'Too many.');

        self::assertFalse((new AnalysisResult(['src' => $listing], []))->hasViolations());
        self::assertTrue((new AnalysisResult(['src' => $listing], [$violation]))->hasViolations());
    }

    public function testViolationCountCountsViolations(): void
    {
        $violation = new Violation('src', 'max_files', 'src', 2, 1, 'Too many.');

        self::assertSame(2, (new AnalysisResult([], [$violation, $violation]))->violationCount());
    }

    public function testDirectoryCountCountsListings(): void
    {
        $result = new AnalysisResult([
            'src' => new DirectoryListing('src', [], ['A']),
            'src/A' => new DirectoryListing('src/A', ['One.php'], []),
        ], []);

        self::assertSame(2, $result->directoryCount());
    }

    public function testFileCountSumsListingFiles(): void
    {
        $result = new AnalysisResult([
            'src' => new DirectoryListing('src', ['Root.php'], ['A']),
            'src/A' => new DirectoryListing('src/A', ['One.php', 'Two.php'], []),
        ], []);

        self::assertSame(3, $result->fileCount());
    }
}
