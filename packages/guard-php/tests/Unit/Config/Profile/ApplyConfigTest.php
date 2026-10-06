<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Profile;

use Guard\Config\Profile\ApplyConfig;
use Guard\Config\Profile\ApplyRuleConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Profile\ApplyConfig
 * @uses \Guard\Config\Profile\ApplyRuleConfig
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
