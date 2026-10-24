<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\TestDependencyConfigReader;
use Guard\Config\Schema;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\TestDependency\TestDependencyRules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\TestDependencyConfigReader
 * @uses \Guard\Config\Schema
 * @uses \Guard\Policy\TestDependency\TestDependencyRules
 */
#[CoversClass(TestDependencyConfigReader::class)]
#[UsesClass(Schema::class)]
#[UsesClass(TestDependencyRules::class)]
final class TestDependencyConfigReaderTest extends TestCase
{
    public function testReadAcceptsACustomRootConfiguration(): void
    {
        $rules = (new TestDependencyConfigReader())->read(['deptrac' => 'architecture.yml', 'source_layers' => ['Domain']]);
        self::assertSame(['composer.json' => 'json', 'architecture.yml' => 'yaml'], $rules->files());
    }

    public function testReadRejectsATestLayerAsProduction(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('cannot contain "Tests"');
        (new TestDependencyConfigReader())->read(['source_layers' => ['Tests']]);
    }

    public function testReadRejectsConfigurationOutsideTheProjectRoot(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('project root');
        (new TestDependencyConfigReader())->read(['deptrac' => '../deptrac.yaml']);
    }

    public function testReadRejectsEmptyProductionLayers(): void
    {
        $this->expectException(PolicyException::class);
        (new TestDependencyConfigReader())->read(['source_layers' => []]);
    }
}
