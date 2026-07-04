<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\FileMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileMetric::class)]
final class FileMetricTest extends TestCase
{
    public function testStoresFileLineMetrics(): void
    {
        $metric = new FileMetric('src/A.php', 10, 7);

        self::assertSame('src/A.php', $metric->path);
        self::assertSame(10, $metric->physicalLines);
        self::assertSame(7, $metric->nonCommentLines);
    }
}
