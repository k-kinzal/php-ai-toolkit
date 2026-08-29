<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;

#[CoversClass(ApplyRuleConfig::class)]
final class ApplyRuleConfigTest extends TestCase
{
    public function testStoresNamedPolicyAssignment(): void
    {
        $rule = new ApplyRuleConfig('native', ['src/Native*.php'], 'native-api');

        self::assertSame('native', $rule->name);
        self::assertSame(['src/Native*.php'], $rule->paths);
        self::assertSame('native-api', $rule->policy);
    }
}
