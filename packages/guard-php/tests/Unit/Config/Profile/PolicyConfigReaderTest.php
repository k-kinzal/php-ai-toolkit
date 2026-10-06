<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Profile;

use Guard\Config\Profile\PolicyConfigReader;
use Guard\Config\Profile\PolicyDefinition;
use Guard\Config\Reader\LimitConfigReader;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Profile\PolicyConfigReader
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
 * @uses \Guard\Config\Profile\PolicyDefinition
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
#[CoversClass(PolicyConfigReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\Profile\PolicyConfig::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(MetricConfigKeyValidator::class)]
#[UsesClass(MetricConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Value\LimitConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class PolicyConfigReaderTest extends TestCase
{
    public function testReadReturnsUnresolvedPolicyDefinition(): void
    {
        $definition = (new PolicyConfigReader())->read('native', [
            'extends' => 'standard',
            'limits' => ['file' => ['lines' => 900]],
        ]);

        self::assertSame('native', $definition->name);
        self::assertSame('standard', $definition->extends);
        self::assertSame(['file.lines' => 900], $definition->limitPatch);
    }
}
