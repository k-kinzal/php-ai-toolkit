<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ErrorFormatter;

use PhpAiToolkit\PhpStan\ErrorFormatter\ErrorCollectionSummary;
use PHPStan\Analyser\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ErrorCollectionSummary::class)]
final class ErrorCollectionSummaryTest extends TestCase
{
    public function testHumanMessageIncludesErrorsFilesAndWarnings(): void
    {
        $summary = new ErrorCollectionSummary();

        self::assertSame('Found 2 errors in 1 file and 1 warning', $summary->humanMessage(2, 1, 1));
    }

    public function testUniqueFileCountCountsDistinctErrorFiles(): void
    {
        $summary = new ErrorCollectionSummary();

        self::assertSame(2, $summary->uniqueFileCount([
            new Error('A', '/tmp/A.php', 1),
            new Error('B', '/tmp/A.php', 2),
            new Error('C', '/tmp/B.php', 3),
        ]));
    }
}
