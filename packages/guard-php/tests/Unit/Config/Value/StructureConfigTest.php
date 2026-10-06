<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Value;

use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Config\Value\StructureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Value\StructureConfig
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 */
#[CoversClass(StructureConfig::class)]
#[UsesClass(DirectoryRuleConfig::class)]
final class StructureConfigTest extends TestCase
{
    public function testStoresConfigData(): void
    {
        $rule = new DirectoryRuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, null);
        $config = new StructureConfig('/project', ['src'], ['*.tmp'], [$rule]);

        self::assertSame('/project', $config->root);
        self::assertSame(['src'], $config->paths);
        self::assertSame(['*.tmp'], $config->exclude);
        self::assertSame([$rule], $config->rules);
    }
}
