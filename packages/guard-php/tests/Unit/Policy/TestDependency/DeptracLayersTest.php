<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\TestDependency;

use Guard\Diagnostic\PolicyException;
use Guard\Policy\TestDependency\DeptracLayers;
use Guard\Policy\TestDependency\DeptracRules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Guard\Policy\TestDependency\DeptracLayers
 * @uses \Guard\Policy\TestDependency\DeptracRules
 */
#[CoversClass(DeptracLayers::class)]
#[UsesClass(DeptracRules::class)]
final class DeptracLayersTest extends TestCase
{
    public function testBuildPreservesCustomLayerSettingsAndAddsOnlyActiveTests(): void
    {
        $layers = (new DeptracLayers())->build([(object) ['name' => 'Domain', 'collectors' => [(object) ['type' => 'classLike', 'value' => '^App\\Domain']], 'attributes' => (object) ['color' => 'blue']]], ['bench' => 'Bench'], ['Domain', 'Src']);
        self::assertCount(3, $layers);
        self::assertSame('Domain', $layers[0]->name);
        self::assertEquals((object) ['color' => 'blue'], $layers[0]->attributes);
        self::assertSame('Src', $layers[1]->name);
        self::assertSame('Bench', $layers[2]->name);
    }

    public function testBuildRejectsMissingCustomSourceLayers(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Production layer "Domain" is missing');
        (new DeptracLayers())->build(null, ['tests' => 'Tests'], ['Domain', 'Src']);
    }

    public function testTestCollectorsMatchNamespaceBoundariesAndRootDirectories(): void
    {
        $collectors = (new DeptracLayers())->testCollectors('Bench', 'bench');
        self::assertIsString($collectors[0]->value);
        self::assertIsString($collectors[1]->value);
        self::assertSame(1, preg_match('~' . $collectors[0]->value . '~', 'Bench\\Case'));
        self::assertSame(0, preg_match('~' . $collectors[0]->value . '~', 'Benchmark\\Case'));
        $pattern = str_replace('%projectDirectory%', '/project', $collectors[1]->value);
        self::assertSame(1, preg_match('~' . $pattern . '~', '/project/bench/run.php'));
        self::assertSame(0, preg_match('~' . $pattern . '~', '/project/tests/bench/run.php'));
    }

    public function testSourceCollectorsConfinesBroadPatternsAndIsIdempotent(): void
    {
        $builder = new DeptracLayers();
        $once = $builder->sourceCollectors([(object) ['type' => 'classLike', 'value' => '.*']]);
        self::assertEquals($once, $builder->sourceCollectors($once));
        self::assertSame('bool', $once[0]->type);
        self::assertIsArray($once[0]->must);
        self::assertInstanceOf(stdClass::class, $once[0]->must[0]);
        self::assertSame('directory', $once[0]->must[0]->type);
    }

    public function testFallbackCollectorsExcludeExistingProductionLayers(): void
    {
        $collectors = (new DeptracLayers())->fallbackCollectors(['Domain', 'Src']);
        self::assertIsArray($collectors[0]->must_not);
        self::assertInstanceOf(stdClass::class, $collectors[0]->must_not[0]);
        self::assertSame('Domain', $collectors[0]->must_not[0]->value);
    }
}
