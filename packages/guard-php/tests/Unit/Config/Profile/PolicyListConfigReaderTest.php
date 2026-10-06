<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Profile;

use Guard\Config\Profile\PolicyConfig;
use Guard\Config\Profile\PolicyConfigReader;
use Guard\Config\Profile\PolicyDefinition;
use Guard\Config\Profile\PolicyListConfigReader;
use Guard\Config\Profile\PolicyResolver;
use Guard\Config\Reader\LimitConfigReader;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Config\Value\LimitConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Profile\PolicyListConfigReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Profile\PolicyConfig
 * @uses \Guard\Config\Profile\PolicyConfigReader
 * @uses \Guard\Config\Profile\PolicyDefinition
 * @uses \Guard\Config\Profile\PolicyResolver
 * @uses \Guard\Config\Reader\LimitConfigReader
 * @uses \Guard\Config\Validation\MetricConfigKeyValidator
 * @uses \Guard\Config\Validation\MetricConfigScalarReader
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(PolicyListConfigReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(PolicyConfigReader::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(PolicyResolver::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(MetricConfigKeyValidator::class)]
#[UsesClass(MetricConfigScalarReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class PolicyListConfigReaderTest extends TestCase
{
    public function testReadReturnsResolvedNamedPolicies(): void
    {
        $policies = (new PolicyListConfigReader())->read([
            'standard' => ['limits' => ['file' => ['lines' => 500]]],
            'native' => [
                'extends' => 'standard',
                'limits' => ['file' => ['lines' => 900]],
            ],
        ]);

        self::assertSame(500, $policies['standard']->limits->maxFileLines);
        self::assertSame(900, $policies['native']->limits->maxFileLines);
    }
}
