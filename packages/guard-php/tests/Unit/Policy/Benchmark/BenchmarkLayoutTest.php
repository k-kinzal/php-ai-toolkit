<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Benchmark;

use Guard\Diagnostic\Finding;
use Guard\Input\DirectoryListing;
use Guard\Policy\Benchmark\BenchmarkLayout;
use Guard\Policy\Benchmark\BenchmarkRules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Benchmark\BenchmarkLayout
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Policy\Benchmark\BenchmarkRules
 */
#[CoversClass(BenchmarkLayout::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(Finding::class)]
#[UsesClass(BenchmarkRules::class)]
final class BenchmarkLayoutTest extends TestCase
{
    public function testValidNameUsesTheTotalFileCount(): void
    {
        self::assertTrue((new BenchmarkLayout())->validName('Bench.php', 1));
        self::assertFalse((new BenchmarkLayout())->validName('Bench.php', 2));
        self::assertTrue((new BenchmarkLayout())->validName('ParserBench.php', 2));
        self::assertFalse((new BenchmarkLayout())->validName('ParserBench.php', 1));
    }

    public function testCollisionsIdentifyBothFilesAndTheConflictingCommand(): void
    {
        $findings = (new BenchmarkLayout())->collisions(['XMLBench.php', 'XmlBench.php']);
        self::assertCount(1, $findings);
        self::assertSame('phpbench.command_collision', $findings[0]->rule);
        self::assertStringContainsString('XMLBench.php', $findings[0]->message);
        self::assertStringContainsString('XmlBench.php', $findings[0]->message);
        self::assertStringContainsString('bench:xml', $findings[0]->message);
    }

    /**
     * @param list<string> $files
     * @param list<string> $directories
     * @param list<string> $expected
     * @dataProvider providerLayouts
     */
    #[DataProvider('providerLayouts')]
    public function testFindingsDescribeEveryInvalidEntry(array $files, array $directories, array $expected): void
    {
        $findings = (new BenchmarkLayout())->findings(new DirectoryListing('bench', $files, $directories));
        self::assertSame($expected, array_map(static fn (Finding $finding): string => $finding->path . ':' . $finding->rule, $findings));
    }

    /**
     * @return iterable<string, array{list<string>, list<string>, list<string>}>
     */
    public static function providerLayouts(): iterable
    {
        yield 'single' => [['Bench.php'], [], []];
        yield 'multiple' => [['ParserBench.php', 'HttpClientBench.php'], [], []];
        yield 'empty' => [[], [], ['bench:phpbench.empty']];
        yield 'single descriptive name' => [['ParserBench.php'], [], ['bench/ParserBench.php:phpbench.filename']];
        yield 'multiple without prefix' => [['Bench.php', 'ParserBench.php'], [], ['bench/Bench.php:phpbench.filename']];
        yield 'lowercase name' => [['parserBench.php', 'HttpClientBench.php'], [], ['bench/parserBench.php:phpbench.filename']];
        yield 'wrong suffix' => [['Parser.php', 'HttpClientBench.php'], [], ['bench/Parser.php:phpbench.filename']];
        yield 'non PHP and hidden files' => [['ParserBench.php', 'README.md', '.gitkeep'], [], ['bench/README.md:phpbench.extension', 'bench/.gitkeep:phpbench.extension']];
        yield 'subdirectories' => [['Bench.php'], ['Nested', 'Fake.php'], ['bench/Nested:phpbench.directory', 'bench/Fake.php:phpbench.directory']];
        yield 'all direct files count' => [['Bench.php', 'data.json'], [], ['bench/Bench.php:phpbench.filename', 'bench/data.json:phpbench.extension']];
    }
}
