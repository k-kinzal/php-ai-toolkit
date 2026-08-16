<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Cli\DocGenConfigOverrides;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocGenConfigOverrides::class)]
#[UsesClass(DocGenConfig::class)]
final class DocGenConfigOverridesTest extends TestCase
{
    public function testApplyOverridesOutputAndCoverage(): void
    {
        $config = new DocGenConfig('/proj', ['.'], [], ['src/Generated'], 'build/docs', 'My Title', 'deptrac.yaml', null);
        $arguments = ['config' => null, 'output' => 'public/docs', 'vendor' => null, 'vendorDev' => null, 'coverage' => 'build/coverage-xml', 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'help' => false, 'version' => false];

        $applied = (new DocGenConfigOverrides())->apply($config, $arguments);

        self::assertSame('/proj', $applied->root);
        self::assertSame(['.'], $applied->packages);
        self::assertSame([], $applied->vendor);
        self::assertSame([], $applied->vendorDev);
        self::assertSame(['src/Generated'], $applied->exclude);
        self::assertSame('public/docs', $applied->output);
        self::assertSame('My Title', $applied->title);
        self::assertSame('deptrac.yaml', $applied->deptrac);
        self::assertSame('build/coverage-xml', $applied->coverage);
    }

    public function testApplyMergesVendorGlobsOntoConfiguredList(): void
    {
        $config = new DocGenConfig('/proj', ['.'], ['configured/*'], [], 'build/docs', null, null, null);
        $arguments = ['config' => null, 'output' => null, 'vendor' => ['cli/*', 'extra'], 'vendorDev' => null, 'coverage' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'help' => false, 'version' => false];

        $applied = (new DocGenConfigOverrides())->apply($config, $arguments);

        self::assertSame(['configured/*', 'cli/*', 'extra'], $applied->vendor);
        self::assertSame([], $applied->vendorDev);
    }

    public function testApplyMergesVendorDevGlobsOntoConfiguredList(): void
    {
        $config = new DocGenConfig('/proj', ['.'], [], [], 'build/docs', null, null, null, ['configured/*']);
        $arguments = ['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => ['phpunit/*'], 'coverage' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'help' => false, 'version' => false];

        $applied = (new DocGenConfigOverrides())->apply($config, $arguments);

        self::assertSame(['configured/*', 'phpunit/*'], $applied->vendorDev);
        self::assertSame([], $applied->vendor);
    }

    public function testApplyKeepsConfigurationWhenOverridesAreNull(): void
    {
        $config = new DocGenConfig('/proj', ['.'], ['a/*'], [], 'site', null, null, 'cov', ['b/*']);
        $arguments = ['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'help' => false, 'version' => false];

        $applied = (new DocGenConfigOverrides())->apply($config, $arguments);

        self::assertSame(['a/*'], $applied->vendor);
        self::assertSame(['b/*'], $applied->vendorDev);
        self::assertSame('site', $applied->output);
        self::assertSame('cov', $applied->coverage);
        self::assertNull($applied->title);
    }
}
