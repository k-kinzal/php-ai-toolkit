<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\CyclomaticComplexityCalculator;
use PhpAiToolkit\LocGuard\Analysis\FileAnalysis;
use PhpAiToolkit\LocGuard\Analysis\FileMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\LocGuardAnalyzer;
use PhpAiToolkit\LocGuard\Analysis\PhpFileAnalyzer;
use PhpAiToolkit\LocGuard\Analysis\TokenLineCounter;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\Filesystem\PhpFileFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocGuardAnalyzer::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ClassLikeMetricCollector::class)]
#[UsesClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(FileAnalysis::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricCollector::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(PhpFileAnalyzer::class)]
#[UsesClass(PhpFileFinder::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(TokenLineCounter::class)]
#[UsesClass(Violation::class)]
final class LocGuardAnalyzerTest extends TestCase
{
    public function testAnalyzesConfiguredSourceFiles(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-analyzer-' . bin2hex(random_bytes(4));
        mkdir($dir);
        mkdir($dir . '/src');
        file_put_contents($dir . '/src/Example.php', <<<'PHP'
<?php

function too_long(): void
{
    echo '1';
    echo '2';
}
PHP);

        $result = (new LocGuardAnalyzer())->analyze(new LocGuardConfig(
            $dir,
            ['src'],
            [],
            new LimitConfig(100, 100, 100, 100, 100, 100, 3, 50, 20),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));

        self::assertSame(1, $result->fileCount());
        self::assertSame('function_lines', $result->violations[0]->rule);
        self::assertSame('src/Example.php', $result->violations[0]->path);
    }
}
