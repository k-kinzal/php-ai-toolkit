<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Layer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Layer\DeptracConfigReader;
use Toolkit\DocGen\Analysis\Layer\LayerCollector;
use Toolkit\DocGen\Analysis\Layer\LayerDefinition;
use Toolkit\DocGen\Analysis\Layer\LayerModel;
use Toolkit\DocGen\DocGenException;

/**
 * @covers \Toolkit\DocGen\Analysis\Layer\DeptracConfigReader
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerCollector
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerDefinition
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerModel
 */
#[CoversClass(DeptracConfigReader::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(LayerCollector::class)]
#[UsesClass(LayerDefinition::class)]
#[UsesClass(LayerModel::class)]
final class DeptracConfigReaderTest extends TestCase
{
    public function testReadParsesTheModernWrappedForm(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-layer-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/deptrac.yaml', <<<'YAML'
deptrac:
  layers:
    - name: Domain
      collectors:
        - type: directory
          value: 'src/Domain/.*'
    - name: Application
      collectors: ~
  ruleset:
    Domain: ~
    Application:
      - Domain
YAML);

        $model = (new DeptracConfigReader())->read($dir . '/deptrac.yaml');

        self::assertCount(2, $model->layers);
        self::assertSame('Domain', $model->layers[0]->name);
        self::assertCount(1, $model->layers[0]->collectors);
        self::assertSame('directory', $model->layers[0]->collectors[0]->type);
        self::assertSame('src/Domain/.*', $model->layers[0]->collectors[0]->value);
        self::assertSame('Application', $model->layers[1]->name);
        self::assertSame([], $model->layers[1]->collectors);
        self::assertSame(['Domain' => [], 'Application' => ['Domain']], $model->ruleset);
    }

    public function testReadParsesTheLegacyTopLevelForm(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-layer-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/deptrac.yaml', <<<'YAML'
layers:
  - name: Infra
    collectors:
      - type: className
        value: 'Acme.*'
ruleset:
  Infra: []
YAML);

        $model = (new DeptracConfigReader())->read($dir . '/deptrac.yaml');

        self::assertCount(1, $model->layers);
        self::assertSame('Infra', $model->layers[0]->name);
        self::assertCount(1, $model->layers[0]->collectors);
        self::assertSame('className', $model->layers[0]->collectors[0]->type);
        self::assertSame('Acme.*', $model->layers[0]->collectors[0]->value);
        self::assertSame(['Infra' => []], $model->ruleset);
    }

    public function testReadRejectsMissingFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-layer-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Deptrac config not found: ' . $dir . '/deptrac.yaml');

        (new DeptracConfigReader())->read($dir . '/deptrac.yaml');
    }

    public function testReadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-layer-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/deptrac.yaml', <<<'YAML'
layers: "unclosed
YAML);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid deptrac.yaml: Malformed inline YAML string');

        (new DeptracConfigReader())->read($dir . '/deptrac.yaml');
    }

    public function testReadRejectsScalarTopLevel(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-layer-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/deptrac.yaml', <<<'YAML'
just a string
YAML);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid deptrac.yaml: top-level value must be a mapping.');

        (new DeptracConfigReader())->read($dir . '/deptrac.yaml');
    }

    public function testLayersSkipsEntriesAndCollectorsWithoutRequiredKeys(): void
    {
        $layers = (new DeptracConfigReader())->layers(['layers' => [
            ['name' => 'Domain', 'collectors' => [['type' => 'directory', 'value' => 'src/.*'], ['type' => 'directory'], 'scalar']],
            ['collectors' => [['type' => 'directory', 'value' => 'src/.*']]],
            'scalar',
        ]]);

        self::assertCount(1, $layers);
        self::assertSame('Domain', $layers[0]->name);
        self::assertCount(1, $layers[0]->collectors);
        self::assertSame('directory', $layers[0]->collectors[0]->type);
        self::assertSame('src/.*', $layers[0]->collectors[0]->value);
        self::assertSame([], (new DeptracConfigReader())->layers([]));
    }

    public function testRulesetKeepsOnlyStringAllowances(): void
    {
        $ruleset = (new DeptracConfigReader())->ruleset(['ruleset' => ['A' => ['B', 'C', 5], 'B' => null, 'C' => 'scalar']]);

        self::assertSame(['A' => ['B', 'C'], 'B' => [], 'C' => []], $ruleset);
        self::assertSame([], (new DeptracConfigReader())->ruleset([]));
    }
}
