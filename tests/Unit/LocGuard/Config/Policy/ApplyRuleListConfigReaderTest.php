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
use Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader;
use Toolkit\LocGuard\LocGuardException;

#[CoversClass(ApplyRuleListConfigReader::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(ApplyRuleConfigReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
final class ApplyRuleListConfigReaderTest extends TestCase
{
    public function testReadReturnsValidatedRules(): void
    {
        $rules = (new ApplyRuleListConfigReader())->read([[
            'name' => 'native',
            'match' => ['paths' => ['src/Native/**']],
            'policy' => 'native-api',
        ]]);

        self::assertSame('native', $rules[0]->name);
    }

    public function testReadRejectsDuplicateRuleNames(): void
    {
        $rule = [
            'name' => 'native',
            'match' => ['paths' => ['src/Native/**']],
            'policy' => 'native-api',
        ];

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('duplicate apply rule name "native"');
        (new ApplyRuleListConfigReader())->read([$rule, $rule]);
    }
}
