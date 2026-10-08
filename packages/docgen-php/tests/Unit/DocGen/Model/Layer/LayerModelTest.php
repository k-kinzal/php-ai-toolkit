<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Model\Layer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Model\Layer\LayerDefinition;
use Toolkit\DocGen\Model\Layer\LayerModel;

/**
 * @covers \Toolkit\DocGen\Model\Layer\LayerModel
 * @uses \Toolkit\DocGen\Model\Layer\LayerDefinition
 */
#[CoversClass(LayerModel::class)]
#[UsesClass(LayerDefinition::class)]
final class LayerModelTest extends TestCase
{
    public function testStoresModelData(): void
    {
        $definition = new LayerDefinition('Domain', []);

        $model = new LayerModel([$definition], ['Domain' => [], 'Application' => ['Domain']]);

        self::assertSame([$definition], $model->layers);
        self::assertSame(['Domain' => [], 'Application' => ['Domain']], $model->ruleset);
    }
}
