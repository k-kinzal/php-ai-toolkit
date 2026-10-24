<?php

declare(strict_types=1);

namespace Tests\Integration;

use FilesystemIterator;
use Guard\Cli\Application;
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
final class TestDependenciesTest extends TestCase
{
    public function testInitCheckDryRunAndFixConfigureAllDirectoriesAndPreserveSettings(): void
    {
        $root = $this->project(['bench', 'fuzz', 'spec', 'tests']);
        file_put_contents($root . '/composer.json', '{"name":"example/project","autoload":{"psr-4":{"App\\\\":"src/"}},"scripts":{"custom":"echo custom"}}');
        $original = file_get_contents($root . '/composer.json');
        $app = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(0, $app->run(['init', '--import=test-dependencies']));
            self::assertSame(1, $app->run(['check']));
            self::assertSame(0, $app->run(['fix', '--dry-run']));
            self::assertSame($original, file_get_contents($root . '/composer.json'));
            self::assertFileDoesNotExist($root . '/deptrac.yaml');
            self::assertSame(0, $app->run(['fix']));
            self::assertSame(0, $app->run(['check']));
            $composer = json_decode(file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame(['Bench\\' => 'bench/', 'Fuzz\\' => 'fuzz/', 'Spec\\' => 'spec/', 'Tests\\' => 'tests/'], $composer['autoload-dev']['psr-4']);
            self::assertSame(['App\\' => 'src/'], $composer['autoload']['psr-4']);
            self::assertSame('echo custom', $composer['scripts']['custom']);
            $deptrac = file_get_contents($root . '/deptrac.yaml');
            self::assertSame(0, $app->run(['fix']));
            self::assertSame($deptrac, file_get_contents($root . '/deptrac.yaml'));
        } finally {
            $this->remove($root);
        }
    }

    public function testNewDirectoriesAreDetectedAfterInitialization(): void
    {
        $root = $this->project([]);
        file_put_contents($root . '/guard.yaml', "version: 1\ntest_dependencies: {}\n");
        $app = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(0, $app->run(['check']));
            self::assertSame(0, $app->run(['fix']));
            self::assertFileDoesNotExist($root . '/deptrac.yaml');
            mkdir($root . '/bench');
            self::assertSame(1, $app->run(['check']));
            self::assertSame(0, $app->run(['fix']));
            $data = Yaml::parseFile($root . '/deptrac.yaml');
            self::assertSame(['Src', 'Bench'], array_column($data['deptrac']['layers'], 'name'));
            self::assertSame(0, $app->run(['check']));
        } finally {
            $this->remove($root);
        }
    }

    public function testExistingArchitectureSurvivesAndReverseEdgesAreRemoved(): void
    {
        $root = $this->project(['tests']);
        file_put_contents($root . '/deptrac.yml', "deptrac:\n  paths: [src]\n  layers:\n    - {name: Domain, collectors: [{type: directory, value: 'src/Domain/.*'}]}\n    - {name: Application, collectors: [{type: directory, value: 'src/Application/.*'}]}\n  ruleset:\n    Domain: ['+Tests']\n    Application: [Domain, Tests]\n");
        $app = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(0, $app->run(['init', '--import=test-dependencies']));
            self::assertSame(0, $app->run(['fix']));
            $data = Yaml::parseFile($root . '/deptrac.yml');
            self::assertSame([], $data['deptrac']['ruleset']['Domain']);
            self::assertSame(['Domain'], $data['deptrac']['ruleset']['Application']);
            self::assertSame(['Domain', 'Application', 'Src'], $data['deptrac']['ruleset']['Tests']);
            self::assertSame(0, $app->run(['check']));
        } finally {
            $this->remove($root);
        }
    }

    public function testConflictingFieldRulesBlockEveryWrite(): void
    {
        $root = $this->project(['bench']);
        file_put_contents($root . '/guard.yaml', "version: 1\ntest_dependencies: {}\nconfiguration:\n  - id: conflicting-autoload\n    file: composer.json\n    select: '/autoload-dev/psr-4/Bench\\'\n    assert: {equals: elsewhere/}\n");
        $app = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(1, $app->run(['fix']));
            self::assertSame('{}', file_get_contents($root . '/composer.json'));
            self::assertFileDoesNotExist($root . '/deptrac.yaml');
        } finally {
            $this->remove($root);
        }
    }

    public function testImportedDeptracOverridesBlockTheRepair(): void
    {
        $root = $this->project(['tests']);
        file_put_contents($root . '/guard.yaml', "version: 1\ntest_dependencies: {}\n");
        file_put_contents($root . '/deptrac.yaml', "imports: [baseline.yaml]\n");
        $app = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(1, $app->run(['fix']));
            self::assertSame('{}', file_get_contents($root . '/composer.json'));
            self::assertSame("imports: [baseline.yaml]\n", file_get_contents($root . '/deptrac.yaml'));
        } finally {
            $this->remove($root);
        }
    }

    /**
     * @dataProvider providerFuzzEntrypoints
     */
    #[DataProvider('providerFuzzEntrypoints')]
    public function testStandaloneFuzzFilesCannotReferenceTests(string $source): void
    {
        $root = $this->project(['fuzz', 'tests']);
        file_put_contents($root . '/guard.yaml', "version: 1\ntest_dependencies: {}\n");
        file_put_contents($root . '/tests/Target.php', '<?php namespace Tests; final class Target {}');
        file_put_contents($root . '/fuzz/run.php', $source);
        $app = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(0, $app->run(['fix']));
            $process = proc_open([PHP_BINARY, dirname(__DIR__, 2) . '/vendor/bin/deptrac', 'analyse', '--config-file=deptrac.yaml', '--no-cache', '--no-progress'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(1, proc_close($process), $output);
            self::assertStringContainsString('Tests\\Target', $output);
        } finally {
            $this->remove($root);
        }
    }

    /**
     * @dataProvider providerDependencies
     */
    #[DataProvider('providerDependencies')]
    public function testGeneratedConfigurationEnforcesDependenciesInDeptrac(string $from, string $to, int $expected): void
    {
        $root = $this->project(['bench', 'fuzz', 'spec', 'tests']);
        file_put_contents($root . '/guard.yaml', "version: 1\ntest_dependencies: {}\n");
        $directories = ['Src' => 'src', 'Bench' => 'bench', 'Fuzz' => 'fuzz', 'Spec' => 'spec', 'Tests' => 'tests'];
        file_put_contents($root . '/' . $directories[$to] . '/Target.php', '<?php namespace ' . $to . '; final class Target {}');
        file_put_contents($root . '/' . $directories[$from] . '/Caller.php', '<?php namespace ' . $from . '; final class Caller { public function call(): \\' . $to . '\\Target { return new \\' . $to . '\\Target(); } }');
        $app = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(0, $app->run(['fix']));
            $process = proc_open([PHP_BINARY, dirname(__DIR__, 2) . '/vendor/bin/deptrac', 'analyse', '--config-file=deptrac.yaml', '--no-cache', '--no-progress'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame($expected, proc_close($process), $output);
            self::assertDoesNotMatchRegularExpression('/Uncovered\s+[1-9]/', $output);
        } finally {
            $this->remove($root);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function providerFuzzEntrypoints(): iterable
    {
        yield 'file' => ['<?php new \\Tests\\Target();'];
        yield 'function' => ['<?php namespace Fuzz; function fuzz(): void { new \\Tests\\Target(); }'];
        yield 'closure' => ['<?php $fuzz = static function (): void { new \\Tests\\Target(); };'];
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function providerDependencies(): iterable
    {
        foreach (['Src', 'Bench', 'Fuzz', 'Spec', 'Tests'] as $from) {
            foreach (['Src', 'Bench', 'Fuzz', 'Spec', 'Tests'] as $to) {
                yield $from . ' -> ' . $to => [$from, $to, $from === $to || $to === 'Src' ? 0 : 1];
            }
        }
    }

    /**
     * @param list<string> $directories
     */
    private function project(array $directories): string
    {
        $root = sys_get_temp_dir() . '/guard-test-dependencies-' . uniqid('', true);
        mkdir($root . '/src', 0777, true);
        foreach ($directories as $directory) {
            mkdir($root . '/' . $directory);
        }
        file_put_contents($root . '/composer.json', '{}');
        return $root;
    }

    private function remove(string $root): void
    {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($root);
    }
}
