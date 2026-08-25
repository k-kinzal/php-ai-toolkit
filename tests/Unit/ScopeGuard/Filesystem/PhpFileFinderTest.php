<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Config\ReportConfig;
use Toolkit\ScopeGuard\Config\ScopeGuardConfig;
use Toolkit\ScopeGuard\Filesystem\PhpFileFinder;
use Toolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\ScopeGuard\Filesystem\PhpPathFileCollector;
use Toolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver;
use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * @covers \Toolkit\ScopeGuard\Filesystem\PhpFileFinder
 * @uses \Toolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\ScopeGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\ScopeGuard\Config\ReportConfig
 * @uses \Toolkit\ScopeGuard\Config\ScopeGuardConfig
 * @uses \Toolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver
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
