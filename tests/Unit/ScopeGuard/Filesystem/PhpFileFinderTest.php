<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Filesystem;

use PhpAiToolkit\ScopeGuard\Config\ReportConfig;
use PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpFileFinder;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpPathFileCollector;
use PhpAiToolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Filesystem\PhpFileFinder
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\PhpPathFileCollector
 * @uses \PhpAiToolkit\ScopeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver
 */
#[CoversClass(PhpFileFinder::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ScopeGuardConfig::class)]
#[UsesClass(ScopeGuardPathResolver::class)]
final class PhpFileFinderTest extends TestCase
{
    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testFindReturnsEveryConfiguredSourceFile(ScopeGuardConfig $config): void
    {
        self::assertCount(16, (new PhpFileFinder())->find($config));
    }

    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testFindMapsAbsolutePathsToProjectRelativeOnes(ScopeGuardConfig $config): void
    {
        self::assertContains('src/ForeignRootCaller.php', (new PhpFileFinder())->find($config));
    }

    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testFindSortsTheFilesByAbsolutePath(ScopeGuardConfig $config): void
    {
        $files = (new PhpFileFinder())->find($config);
        $sorted = $files;
        ksort($sorted);

        self::assertSame($sorted, $files);
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
