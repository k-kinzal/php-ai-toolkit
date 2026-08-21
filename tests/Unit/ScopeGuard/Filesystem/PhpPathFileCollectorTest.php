<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Filesystem;

use PhpAiToolkit\ScopeGuard\Config\ReportConfig;
use PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpPathFileCollector;
use PhpAiToolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpPathFileCollector::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ScopeGuardConfig::class)]
#[UsesClass(ScopeGuardException::class)]
#[UsesClass(ScopeGuardPathResolver::class)]
final class PhpPathFileCollectorTest extends TestCase
{
    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testFilesCollectsPhpFilesBelowADirectory(ScopeGuardConfig $config): void
    {
        self::assertCount(16, (new PhpPathFileCollector())->files($config, $config->root . '/src'));
    }

    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testFilesCollectsASingleFile(ScopeGuardConfig $config): void
    {
        self::assertCount(1, (new PhpPathFileCollector())->files($config, $config->root . '/src/ForeignRootCaller.php'));
    }

    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testFilesSkipsASingleNonPhpFile(ScopeGuardConfig $config): void
    {
        self::assertSame([], (new PhpPathFileCollector())->files($config, $config->root . '/scope.yaml'));
    }

    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testFilesRejectsAMissingPath(ScopeGuardConfig $config): void
    {
        $this->expectException(ScopeGuardException::class);

        (new PhpPathFileCollector())->files($config, $config->root . '/absent');
    }

    /**
     * @return array<string, array{ScopeGuardConfig}>
     */
    public static function providerFixtureConfig(): array
    {
        return ['the ScopeGuard fixture project' => [new ScopeGuardConfig(
            __DIR__ . '/../../../Fixture/ScopeGuard/project',
            ['src'],
            [],
            [],
            new ReportConfig('ai', ['path', 'line']),
        )]];
    }
}
