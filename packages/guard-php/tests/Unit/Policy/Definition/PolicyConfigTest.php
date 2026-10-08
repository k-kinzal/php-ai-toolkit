<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Definition;

use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Definition\PolicyConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Definition\PolicyConfig
 * @uses \Guard\Policy\Definition\LimitConfig
 */
#[CoversClass(PolicyConfig::class)]
#[UsesClass(LimitConfig::class)]
final class PolicyConfigTest extends TestCase
{
    public function testStoresEffectivePolicyValues(): void
    {
        $limits = LimitConfig::fromValues(['file.lines' => 900]);
        $policy = new PolicyConfig('native', 'standard', $limits);

        self::assertSame('native', $policy->name);
        self::assertSame('standard', $policy->extends);
        self::assertSame($limits, $policy->limits);
    }
}
