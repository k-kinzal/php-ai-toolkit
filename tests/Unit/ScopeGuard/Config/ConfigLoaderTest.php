<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Config;

use PhpAiToolkit\ScopeGuard\Config\ConfigLoader;
use PhpAiToolkit\ScopeGuard\Config\ConfigScalarReader;
use PhpAiToolkit\ScopeGuard\Config\ConfigStringListReader;
use PhpAiToolkit\ScopeGuard\Config\ReportConfig;
use PhpAiToolkit\ScopeGuard\Config\ReportConfigReader;
use PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Config\ConfigLoader
 * @uses \PhpAiToolkit\ScopeGuard\Config\ConfigScalarReader
 * @uses \PhpAiToolkit\ScopeGuard\Config\ConfigStringListReader
 * @uses \PhpAiToolkit\ScopeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\ScopeGuard\Config\ReportConfigReader
 * @uses \PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig
 * @uses \PhpAiToolkit\ScopeGuard\ScopeGuardException
 */
#[CoversClass(ConfigLoader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(ScopeGuardConfig::class)]
#[UsesClass(ScopeGuardException::class)]
final class ConfigLoaderTest extends TestCase
{
    /**
     * @dataProvider providerConfigFile
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerConfigFile')]
    public function testLoadReadsThePaths(string $path): void
    {
        self::assertSame(['src', 'lib'], (new ConfigLoader())->load($path)->paths);
    }

    /**
     * @dataProvider providerConfigFile
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerConfigFile')]
    public function testLoadReadsTheExclusions(string $path): void
    {
        self::assertSame(['src/Generated/*'], (new ConfigLoader())->load($path)->exclude);
    }

    /**
     * @dataProvider providerConfigFile
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerConfigFile')]
    public function testLoadReadsTheExemptNamespaces(string $path): void
    {
        self::assertSame(['Tests'], (new ConfigLoader())->load($path)->exemptNamespaces);
    }

    /**
     * @dataProvider providerConfigFile
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerConfigFile')]
    public function testLoadReadsTheReportSection(string $path): void
    {
        self::assertSame('json', (new ConfigLoader())->load($path)->report->reporter);
    }

    /**
     * @dataProvider providerConfigFile
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerConfigFile')]
    public function testLoadReadsTheReportOrdering(string $path): void
    {
        self::assertSame(['path', 'line'], (new ConfigLoader())->load($path)->report->orderBy);
    }

    /**
     * @dataProvider providerConfigFile
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerConfigFile')]
    public function testLoadUsesTheConfigDirectoryAsRoot(string $path): void
    {
        self::assertSame(dirname($path), (new ConfigLoader())->load($path)->root);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testLoadRejectsAMissingFile(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ConfigLoader())->load(sys_get_temp_dir() . '/scopeguard-missing-' . uniqid('', true) . '.yaml');
    }

    /**
     * @throws ScopeGuardException
     */
    public function testLoadRejectsANonMapping(): void
    {
        $path = sys_get_temp_dir() . '/scopeguard-scalar-' . uniqid('', true) . '.yaml';
        file_put_contents($path, "just a string\n");

        $this->expectException(ScopeGuardException::class);

        (new ConfigLoader())->load($path);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testLoadRejectsUnparsableYaml(): void
    {
        $path = sys_get_temp_dir() . '/scopeguard-broken-' . uniqid('', true) . '.yaml';
        file_put_contents($path, "paths:\n  - src\n\t- lib\n");

        $this->expectException(ScopeGuardException::class);

        (new ConfigLoader())->load($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function providerConfigFile(): array
    {
        $directory = sys_get_temp_dir() . '/scopeguard-config-' . uniqid('', true);
        mkdir($directory);
        $path = $directory . '/scope.yaml';
        file_put_contents($path, <<<'YAML'
paths:
  - src
  - lib
exclude:
  - 'src/Generated/*'
exempt_namespaces:
  - 'Tests'
report:
  reporter: json
  order_by:
    - path
    - line
YAML);

        return ['a fully populated scope.yaml' => [$path]];
    }
}
