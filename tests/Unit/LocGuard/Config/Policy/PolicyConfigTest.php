<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;

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
