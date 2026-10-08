<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Definition;

use Guard\Policy\Definition\DirectoryRuleConfig;
use Guard\Policy\Definition\StructureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Definition\StructureConfig
 * @uses \Guard\Policy\Definition\DirectoryRuleConfig
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
