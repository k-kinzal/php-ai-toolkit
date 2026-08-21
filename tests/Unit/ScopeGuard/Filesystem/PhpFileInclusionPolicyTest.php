<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Filesystem;

use PhpAiToolkit\ScopeGuard\Config\ReportConfig;
use PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpFileInclusionPolicy::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ScopeGuardConfig::class)]
#[UsesClass(ScopeGuardPathResolver::class)]
final class PhpFileInclusionPolicyTest extends TestCase
{
    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testIncludesAcceptsAPhpFile(ScopeGuardConfig $config): void
    {
        self::assertTrue((new PhpFileInclusionPolicy())->includes($config, '/project/src/Order.php'));
    }

    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testIncludesRejectsANonPhpFile(ScopeGuardConfig $config): void
    {
        self::assertFalse((new PhpFileInclusionPolicy())->includes($config, '/project/src/Order.twig'));
    }

    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testIncludesRejectsAnExcludedFile(ScopeGuardConfig $config): void
    {
        self::assertFalse((new PhpFileInclusionPolicy())->includes($config, '/project/src/Generated/Order.php'));
    }

    /**
     * @return array<string, array{ScopeGuardConfig}>
     */
    public static function providerConfig(): array
    {
        return ['a config excluding generated sources' => [new ScopeGuardConfig(
            '/project',
            ['src'],
            ['src/Generated/*'],
            [],
            new ReportConfig('ai', ['path', 'line']),
        )]];
    }
}
