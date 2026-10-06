<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Tree;

use Guard\Config\Tree\RuleConfig;
use Guard\Config\Tree\StructureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Tree\StructureConfig
 * @uses \Guard\Config\Tree\RuleConfig
 */
#[CoversClass(StructureConfig::class)]
#[UsesClass(RuleConfig::class)]
final class StructureConfigTest extends TestCase
{
    public function testStoresConfigData(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, null);
        $config = new StructureConfig('/project', ['src'], ['*.tmp'], [$rule]);

        self::assertSame('/project', $config->root);
        self::assertSame(['src'], $config->paths);
        self::assertSame(['*.tmp'], $config->exclude);
        self::assertSame([$rule], $config->rules);
    }
}
