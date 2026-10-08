<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Model\Layer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Model\Layer\LayerCollector;
use Toolkit\DocGen\Model\Layer\LayerDefinition;

/**
 * @covers \Toolkit\DocGen\Model\Layer\LayerDefinition
 * @uses \Toolkit\DocGen\Model\Layer\LayerCollector
 */
#[CoversClass(LayerDefinition::class)]
#[UsesClass(LayerCollector::class)]
final class LayerDefinitionTest extends TestCase
{
    public function testStoresLayerData(): void
    {
        $collector = new LayerCollector('directory', 'src/.*');

        $definition = new LayerDefinition('Domain', [$collector]);

        self::assertSame('Domain', $definition->name);
        self::assertSame([$collector], $definition->collectors);
    }

    public function testStoresEmptyCollectorList(): void
    {
        $definition = new LayerDefinition('Application', []);

        self::assertSame('Application', $definition->name);
        self::assertSame([], $definition->collectors);
    }
}
