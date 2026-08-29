<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\ConfigStringListReader;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader;

/**
 * @covers \Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader
 */
#[CoversClass(ApplyRuleConfigReader::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
final class ApplyRuleConfigReaderTest extends TestCase
{
    public function testReadReturnsPathPolicyRule(): void
    {
        $rule = (new ApplyRuleConfigReader())->read([
            'name' => 'native',
            'match' => ['paths' => ['src/Native*.php']],
            'policy' => 'native-api',
        ], 0);

        self::assertSame('native', $rule->name);
        self::assertSame(['src/Native*.php'], $rule->paths);
        self::assertSame('native-api', $rule->policy);
    }
}
