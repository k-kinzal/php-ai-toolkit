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
final class SpecStructureTest extends TestCase
{
    /**
     * @param list<string> $files
     * @param list<string> $directories
     * @param list<string> $expected
     * @dataProvider providerLayouts
     */
    #[DataProvider('providerLayouts')]
    public function testShippedPresetChecksSpecLayouts(array $files, array $directories, array $expected): void
    {
        $root = $this->project($files, $directories);
        try {
            $path = $root . '/guard.yaml';
            $config = (new ConfigurationLoader())->load($path);
            $plan = (new Pipeline())->run($config, $path, false);
            $actual = array_map(static fn (Finding $finding): string => $finding->path . ': ' . $finding->rule, $plan->findings);
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
        yield 'no spec directory' => [[], [], []];
        yield 'root features' => [['spec/login.feature', 'spec/logout.feature', 'spec/Context/AccountContext.php'], [], []];
        yield 'categories and nested categories' => [['spec/Account/login.feature', 'spec/Account/Context/AccountContext.php', 'spec/Billing/Invoice/pay.feature', 'spec/Billing/Invoice/Context/InvoiceContext.php'], [], []];
        yield 'missing root context' => [['spec/login.feature'], [], ['spec/Context: structure.missing_required_dir']];
        yield 'missing category context' => [['spec/Account/login.feature'], ['spec/Context'], ['spec/Account/Context: structure.missing_required_dir']];
        yield 'missing nested category context' => [['spec/Account/Login/login.feature'], ['spec/Account/Context'], ['spec/Account/Login/Context: structure.missing_required_dir']];
        yield 'same named file is not a directory' => [['spec/login.feature', 'spec/Context'], [], ['spec/Context: structure.disallowed_file', 'spec/Context: structure.missing_required_dir']];
        yield 'wrong directory case' => [['spec/login.feature'], ['spec/context'], ['spec/Context: structure.missing_required_dir']];
        yield 'mixed file types' => [['spec/login.feature', 'spec/notes.txt', 'spec/.gitkeep', 'spec/Account.php'], ['spec/Context'], ['spec/notes.txt: structure.disallowed_file', 'spec/.gitkeep: structure.disallowed_file', 'spec/Account.php: structure.disallowed_file']];
        yield 'context file without suffix' => [['spec/Context/Account.php'], [], ['spec/Context/Account.php: structure.disallowed_file']];
        yield 'context file without prefix' => [['spec/Account/Context/Context.php'], [], ['spec/Account/Context/Context.php: structure.disallowed_file']];
        yield 'context file with wrong case' => [['spec/Context/accountContext.php'], [], ['spec/Context/accountContext.php: structure.file_case']];
        yield 'non PHP context file' => [['spec/Context/notes.txt'], [], ['spec/Context/notes.txt: structure.disallowed_file', 'spec/Context/notes.txt: structure.file_case']];
        yield 'nested contexts' => [['spec/Account/login.feature', 'spec/Account/Context/Auth/LoginContext.php'], [], []];
        yield 'invalid nested context' => [['spec/Account/Context/Auth/Login.php'], [], ['spec/Account/Context/Auth/Login.php: structure.disallowed_file']];
        yield 'condition applies only to direct files' => [['spec/notes.txt', 'spec/Account/login.feature'], ['spec/Account/Context'], []];
        yield 'condition ignores feature named directory' => [['spec/notes.txt'], ['spec/login.feature'], []];
        yield 'outside spec is unaffected' => [['features/login.feature', 'features/notes.txt', 'Context/Anything.php'], [], []];
    }

    public function testExcludedFilesDoNotActivateConditionsAndExcludedDirectoriesDoNotSatisfyRequirements(): void
    {
        $root = $this->project(['spec/login.feature', 'spec/notes.txt'], ['spec/Context']);
        try {
            $path = $root . '/guard.yaml';
            $source = file_get_contents($path);
            file_put_contents($path, $source . "structure:\n  exclude: ['*.feature']\n");
            $loader = new ConfigurationLoader();
            self::assertSame([], (new Pipeline())->run($loader->load($path), $path, false)->findings);

            file_put_contents($path, $source . "collect:\n  include: [spec]\n  exclude: [spec/Context, spec/notes.txt]\n");
            $findings = (new Pipeline())->run($loader->load($path), $path, false)->findings;
            self::assertCount(1, $findings);
            self::assertSame('structure.missing_required_dir', $findings[0]->rule);
            self::assertSame('spec/Context', $findings[0]->path);
        } finally {
            $this->remove($root);
        }
    }

    public function testInitCombinesStructureAndSpecAutoloadMapping(): void
    {
        $root = $this->project(['spec/Account/login.feature', 'spec/Account/Context/AccountContext.php'], []);
        try {
            unlink($root . '/guard.yaml');
            file_put_contents($root . '/composer.json', '{}');
            $app = new Application($root, static function (string $text): void {
            });
            self::assertSame(0, $app->run(['init', '--import=structure,test-dependencies']));
            self::assertSame(1, $app->run(['check']));
            self::assertSame(0, $app->run(['fix']));
            self::assertSame(0, $app->run(['check']));
            $composer = json_decode(file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame(['Spec\\' => 'spec/'], $composer['autoload-dev']['psr-4']);
            unlink($root . '/spec/Account/Context/AccountContext.php');
            rmdir($root . '/spec/Account/Context');
            self::assertSame(1, $app->run(['check', '--query=structure']));
        } finally {
            $this->remove($root);
        }
    }

    /**
     * @param list<string> $files
     * @param list<string> $directories
     */
    private function project(array $files, array $directories): string
    {
        $root = sys_get_temp_dir() . '/guard-spec-' . uniqid('', true);
        mkdir($root);
        foreach ($directories as $directory) {
            mkdir($root . '/' . $directory, 0777, true);
        }
        foreach ($files as $file) {
            $path = $root . '/' . $file;
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, '');
        }
        file_put_contents($root . '/guard.yaml', Yaml::dump(['version' => 1, 'imports' => [dirname(__DIR__, 2) . '/rules/structure.yaml']]));

        return $root;
    }

    private function remove(string $root): void
    {
        $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($entries as $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($root);
    }
}
