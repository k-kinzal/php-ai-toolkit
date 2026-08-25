<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\FileAnalysis;
use Toolkit\LocGuard\Analysis\FileMetric\FileMetric;
use Toolkit\LocGuard\Analysis\Violation;

/**
 * @covers \Toolkit\LocGuard\Analysis\FileAnalysis
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetric
 * @uses \Toolkit\LocGuard\Analysis\Violation
 */
#[CoversClass(FileAnalysis::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(Violation::class)]
final class FileAnalysisTest extends TestCase
{
    public function testStoresFileMetricsAndViolations(): void
    {
        $file = new FileMetric('src/A.php', 10, 7);
        $violation = new Violation('src/A.php', 1, 'file_lines', 10, 5, 'Too long.');
        $analysis = new FileAnalysis($file, [$violation]);

        self::assertSame($file, $analysis->file);
        self::assertSame([$violation], $analysis->violations);
    }
}
