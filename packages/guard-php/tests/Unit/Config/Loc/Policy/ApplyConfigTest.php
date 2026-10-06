<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc\Policy;

use Guard\Config\Loc\Policy\ApplyConfig;
use Guard\Config\Loc\Policy\ApplyRuleConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 */
#[CoversClass(ApplyConfig::class)]
#[UsesClass(ApplyRuleConfig::class)]
final class ApplyConfigTest extends TestCase
{
    public function testStoresDefaultPolicyAndRules(): void
    {
        $rule = new ApplyRuleConfig('native', ['src/Native.php'], 'native-api');
        $config = new ApplyConfig('standard', [$rule]);

        self::assertSame('standard', $config->defaultPolicy);
        self::assertSame([$rule], $config->rules);
    }
}
