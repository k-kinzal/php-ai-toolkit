<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Profile;

use Guard\Config\Profile\ApplyRuleConfig;
use Guard\Config\Profile\ApplyRuleConfigReader;
use Guard\Config\Profile\ApplyRuleListConfigReader;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Config\Validation\MetricConfigStringListReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Profile\ApplyRuleListConfigReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Profile\ApplyRuleConfig
 * @uses \Guard\Config\Profile\ApplyRuleConfigReader
 * @uses \Guard\Config\Validation\MetricConfigKeyValidator
 * @uses \Guard\Config\Validation\MetricConfigScalarReader
 * @uses \Guard\Config\Validation\MetricConfigStringListReader
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ApplyRuleListConfigReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(ApplyRuleConfigReader::class)]
#[UsesClass(MetricConfigKeyValidator::class)]
#[UsesClass(MetricConfigScalarReader::class)]
#[UsesClass(MetricConfigStringListReader::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
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
