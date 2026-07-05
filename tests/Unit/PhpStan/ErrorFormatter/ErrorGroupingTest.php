<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ErrorFormatter;

use PhpAiToolkit\PhpStan\ErrorFormatter\ErrorGrouping;
use PHPStan\Analyser\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ErrorGrouping::class)]
final class ErrorGroupingTest extends TestCase
{
    public function testByFileGroupsErrorsByFilePath(): void
    {
        $grouping = new ErrorGrouping();
        $grouped = $grouping->byFile([
            new Error('A', '/tmp/A.php', 1),
            new Error('B', '/tmp/B.php', 1),
            new Error('C', '/tmp/A.php', 2),
        ]);

        self::assertCount(2, $grouped['/tmp/A.php']);
        self::assertCount(1, $grouped['/tmp/B.php']);
    }

    public function testByIdentifierGroupsErrorsByIdentifier(): void
    {
        $grouping = new ErrorGrouping();
        $grouped = $grouping->byIdentifier([
            new Error('A', '/tmp/A.php', 1, true, null, null, null, null, null, 'custom.a'),
            new Error('B', '/tmp/B.php', 1, true, null, null, null, null, null, 'custom.a'),
        ]);

        self::assertCount(2, $grouped['custom.a']);
    }

    public function testShouldDeduplicateReturnsTrueAtThreshold(): void
    {
        $grouping = new ErrorGrouping();

        self::assertTrue($grouping->shouldDeduplicate([
            new Error('A', '/tmp/A.php', 1, true, null, null, null, null, null, 'custom.a'),
            new Error('B', '/tmp/B.php', 2, true, null, null, null, null, null, 'custom.a'),
        ], 2));
    }
}
