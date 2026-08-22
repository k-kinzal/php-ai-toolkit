<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\ExampleCollector;
use PhpAiToolkit\Doctest\Analysis\ImportPreamble;
use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\Analysis\ProjectScanner;
use PhpAiToolkit\Doctest\Analysis\SourceScanner;
use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\Filesystem\DoctestPathResolver;
use PhpAiToolkit\Doctest\Filesystem\PhpFileFinder;
use PhpAiToolkit\Doctest\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\Doctest\Filesystem\PhpPathFileCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectScanner::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(ExampleCollector::class)]
#[UsesClass(SourceScanner::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(ImportPreamble::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PhpFileFinder::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(DoctestPathResolver::class)]
#[UsesClass(DoctestConfig::class)]
#[Medium]
final class ProjectScannerTest extends TestCase
{
    public function testExamplesFindsEveryExampleTheConfigurationSelects(): void
    {
        $config = new DoctestConfig((string) realpath(__DIR__ . '/../../../Fixture/Doctest/project'), ['src'], ['src/Nested/*']);

        $examples = (new ProjectScanner())->examples($config);

        self::assertCount(6, $examples);
        self::assertSame('Tests\Fixture\Doctest\Project\Calculator#1', $examples[0]->id());
        self::assertSame('src/Calculator.php', $examples[0]->target->reportPath());
    }

    public function testExamplesHonoursTheExcludedPaths(): void
    {
        $root = (string) realpath(__DIR__ . '/../../../Fixture/Doctest/project');

        $withNested = (new ProjectScanner())->examples(new DoctestConfig($root, ['src'], []));

        self::assertCount(7, $withNested);
    }

    public function testExamplesInReadsTheExamplesOfEveryGivenFile(): void
    {
        $path = (string) realpath(__DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php');

        $examples = (new ProjectScanner())->examplesIn([$path => 'src/Calculator.php']);

        self::assertCount(6, $examples);
        self::assertSame('src/Calculator.php', $examples[0]->target->reportPath());
    }
}
