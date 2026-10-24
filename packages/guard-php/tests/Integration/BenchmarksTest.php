<?php

declare(strict_types=1);

namespace Tests\Integration;

use FilesystemIterator;
use Guard\Cli\Application;
use Guard\Config\ConfigurationLoader;
use Guard\Diagnostic\Finding;
use Guard\Execution\Pipeline;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversNothing
 * @medium
 */
#[CoversNothing]
#[Medium]
final class BenchmarksTest extends TestCase
{
    public function testInitCheckDryRunAndFixConfigureEveryBenchmarkAndPreserveOtherSettings(): void
    {
        $root = $this->project(['HttpClientBench.php', 'ParserBench.php']);
        $original = '{"name":"example/project","autoload":{"psr-4":{"App\\\\":"src/"}},"scripts":{"custom":"echo custom","bench":"php custom.php","bench:parser":"phpbench run bench/HttpClientBench.php"}}';
        file_put_contents($root . '/composer.json', $original);
        unlink($root . '/guard.yaml');
        $app = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(0, $app->run(['init', '--import=phpbench,test-dependencies']));
            self::assertSame(1, $app->run(['check']));
            self::assertSame(0, $app->run(['fix', '--dry-run']));
            self::assertSame($original, file_get_contents($root . '/composer.json'));
            self::assertSame(0, $app->run(['fix']));
            self::assertSame(0, $app->run(['check']));
            $composer = json_decode(file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('bench/', $composer['autoload-dev']['psr-4']['Bench\\']);
            self::assertSame(['App\\' => 'src/'], $composer['autoload']['psr-4']);
            self::assertSame('echo custom', $composer['scripts']['custom']);
            self::assertSame('phpbench run bench --bootstrap=vendor/autoload.php', $composer['scripts']['bench']);
            self::assertSame('phpbench run bench/HttpClientBench.php --bootstrap=vendor/autoload.php', $composer['scripts']['bench:http-client']);
            self::assertSame('phpbench run bench/ParserBench.php --bootstrap=vendor/autoload.php', $composer['scripts']['bench:parser']);
            $fixed = file_get_contents($root . '/composer.json');
            self::assertSame(0, $app->run(['fix']));
            self::assertSame($fixed, file_get_contents($root . '/composer.json'));
            self::assertFileDoesNotExist($root . '/phpbench.json.dist');
        } finally {
            $this->remove($root);
        }
    }

    public function testOneFileNeedsOnlyTheMainCommandAndNewFilesAreDetected(): void
    {
        $root = $this->project(['Bench.php']);
        $app = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(0, $app->run(['fix']));
            $composer = json_decode(file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame(['bench'], array_keys($composer['scripts']));
            rename($root . '/bench/Bench.php', $root . '/bench/ParserBench.php');
            copy($root . '/bench/ParserBench.php', $root . '/bench/HttpClientBench.php');
            self::assertSame(1, $app->run(['check']));
            self::assertSame(0, $app->run(['fix']));
            self::assertSame(0, $app->run(['check']));
        } finally {
            $this->remove($root);
        }
    }

    /**
     * @param list<string> $files
     * @param list<string> $directories
     * @param list<string> $expected
     * @dataProvider providerLayouts
     */
    #[DataProvider('providerLayouts')]
    public function testImportedPresetReportsLayoutAndNamespaceViolations(array $files, array $directories, array $expected): void
    {
        $root = $this->project($files);
        foreach ($directories as $directory) {
            mkdir($root . '/bench/' . $directory);
        }
        try {
            $path = $root . '/guard.yaml';
            $plan = (new Pipeline())->run((new ConfigurationLoader())->load($path), $path, true);
            $actual = array_map(static fn (Finding $finding): string => $finding->path . ':' . $finding->rule, $plan->findings);
            sort($actual);
            sort($expected);
            self::assertSame($expected, $actual);
        } finally {
            $this->remove($root);
        }
    }

    /**
     * @return iterable<string, array{list<string>, list<string>, list<string>}>
     */
    public static function providerLayouts(): iterable
    {
        yield 'single valid' => [['Bench.php'], [], []];
        yield 'multiple valid' => [['ParserBench.php', 'HttpClientBench.php'], [], []];
        yield 'single wrong name' => [['ParserBench.php'], [], ['bench/ParserBench.php:phpbench.filename']];
        yield 'multiple no prefix' => [['Bench.php', 'ParserBench.php'], [], ['bench/Bench.php:phpbench.filename']];
        yield 'invalid extension' => [['ParserBench.php', 'data.json'], [], ['bench/data.json:phpbench.extension']];
        yield 'nested' => [['Bench.php'], ['Nested'], ['bench/Nested:phpbench.directory']];
        yield 'empty' => [[], [], ['bench:phpbench.empty']];
    }

    public function testCustomBenchmarksAreReportedWithoutExecutingOrRewritingThem(): void
    {
        $root = $this->project(['Bench.php']);
        $source = '<?php /* PhpBench\Attributes */ file_put_contents(__DIR__ . "/executed", "yes");';
        file_put_contents($root . '/bench/Bench.php', $source);
        $output = '';
        $app = new Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            self::assertSame(1, $app->run(['fix']));
            self::assertStringContainsString('phpbench.namespace', $output);
            self::assertStringContainsString('bench/Bench.php', $output);
            self::assertSame($source, file_get_contents($root . '/bench/Bench.php'));
            self::assertFileDoesNotExist($root . '/bench/executed');
        } finally {
            $this->remove($root);
        }
    }

    public function testMissingDirectoryAndMissingComposerProduceActionableErrors(): void
    {
        $root = $this->project([]);
        rmdir($root . '/bench');
        $output = '';
        $app = new Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            self::assertSame(2, $app->run(['check']));
            self::assertStringContainsString('Create bench/Bench.php', $output);
            mkdir($root . '/bench');
            file_put_contents($root . '/bench/Bench.php', '<?php use PhpBench\Attributes as Bench;');
            unlink($root . '/composer.json');
            self::assertSame(1, $app->run(['fix']));
            self::assertStringContainsString('composer.json does not exist', $output);
            self::assertFileDoesNotExist($root . '/composer.json');
        } finally {
            $this->remove($root);
        }
    }

    public function testCatalogAndExplicitOptOutAreAvailableWithoutBenchmarkFiles(): void
    {
        $root = $this->project([]);
        rmdir($root . '/bench');
        $output = '';
        $app = new Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            self::assertSame(0, $app->run(['rules', '--format=json']));
            self::assertStringContainsString('phpbench.namespace', $output);
            file_put_contents($root . '/guard.yaml', "benchmarks: false\n", FILE_APPEND);
            self::assertSame(0, $app->run(['check']));
            file_put_contents($root . '/guard.yaml', "version: 1\nbenchmarks: null\n");
            self::assertSame(2, $app->run(['check']));
            self::assertStringContainsString('benchmarks must be true or false', $output);
        } finally {
            $this->remove($root);
        }
    }

    public function testConflictingComposerConstraintBlocksWrites(): void
    {
        $root = $this->project(['Bench.php']);
        file_put_contents($root . '/guard.yaml', "configuration:\n  - id: conflicting-bench\n    file: composer.json\n    select: /scripts/bench\n    assert: {equals: 'php custom.php'}\n", FILE_APPEND);
        $app = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(1, $app->run(['fix']));
            self::assertSame('{}', file_get_contents($root . '/composer.json'));
        } finally {
            $this->remove($root);
        }
    }

    /**
     * @param list<string> $files
     */
    private function project(array $files): string
    {
        $root = sys_get_temp_dir() . '/guard-benchmarks-' . uniqid('', true);
        mkdir($root . '/bench', 0777, true);
        foreach ($files as $file) {
            file_put_contents($root . '/bench/' . $file, '<?php namespace Bench; use PhpBench\Attributes as Bench; #[Bench\Revs(1)] class ' . pathinfo($file, PATHINFO_FILENAME) . ' { public function benchRun(): void {} }');
        }
        file_put_contents($root . '/composer.json', '{}');
        file_put_contents($root . '/guard.yaml', Yaml::dump(['version' => 1, 'imports' => [dirname(__DIR__, 2) . '/rules/phpbench.yaml']]));
        return $root;
    }

    private function remove(string $root): void
    {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($root);
    }
}
