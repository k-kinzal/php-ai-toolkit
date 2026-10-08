<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Definition;

use Guard\Policy\Definition\ApplyRuleConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Definition\ApplyRuleConfig
 */
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
