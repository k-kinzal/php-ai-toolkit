<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;

/**
 * @covers \Toolkit\LocGuard\Config\Policy\ApplyConfig
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
