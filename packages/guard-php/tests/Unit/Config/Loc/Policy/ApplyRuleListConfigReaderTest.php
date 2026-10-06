<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc\Policy;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Config\Loc\ConfigScalarReader;
use Guard\Config\Loc\ConfigStringListReader;
use Guard\Config\Loc\Policy\ApplyRuleConfig;
use Guard\Config\Loc\Policy\ApplyRuleConfigReader;
use Guard\Config\Loc\Policy\ApplyRuleListConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\Policy\ApplyRuleListConfigReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\ConfigKeyValidator
 * @uses \Guard\Config\Loc\ConfigScalarReader
 * @uses \Guard\Config\Loc\ConfigStringListReader
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfigReader
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ApplyRuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(ApplyRuleConfigReader::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('duplicate apply rule name "native"');
        (new ApplyRuleListConfigReader())->read([$rule, $rule]);
    }
}
