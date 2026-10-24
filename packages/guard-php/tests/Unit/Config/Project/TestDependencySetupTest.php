<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project;

use Guard\Config\Project\Recommendations;
use Guard\Config\Project\TestDependencySetup;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\TestDependencySetup
 * @uses \Guard\Config\Project\Recommendations
 */
#[CoversClass(TestDependencySetup::class)]
#[UsesClass(Recommendations::class)]
final class TestDependencySetupTest extends TestCase
{
    public function testSettingsPreservesProductionLayersAndDetectsYml(): void
    {
        $root = sys_get_temp_dir() . '/guard-dependency-setup-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/deptrac.yml', "deptrac:\n  layers:\n    - {name: Domain, collectors: [{type: directory, value: 'src/Domain/.*'}]}\n    - {name: Tests, collectors: [{type: classLike, value: '^Tests'}]}\n");
        self::assertSame(['deptrac' => 'deptrac.yml', 'source_layers' => ['Domain']], (new TestDependencySetup())->settings($root));
        unlink($root . '/deptrac.yml');
        rmdir($root);
    }

    public function testSettingsDefaultsToSrcBeforeDeptracExists(): void
    {
        self::assertSame(['deptrac' => 'deptrac.yaml', 'source_layers' => ['Src']], (new TestDependencySetup())->settings('/missing-' . uniqid()));
    }
    public function testSourceLayersDefaultsWhenNoProductionDirectoryLayerExists(): void
    {
        self::assertSame(['Src'], (new TestDependencySetup())->sourceLayers([['name' => 'Tests', 'collectors' => []]]));
    }
}
