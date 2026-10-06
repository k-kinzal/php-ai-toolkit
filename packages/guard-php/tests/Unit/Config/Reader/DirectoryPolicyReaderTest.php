<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\DirectoryPolicyReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\DirectoryRuleConfigReader
 * @uses \Guard\Config\Reader\DirectoryRuleListConfigReader
 * @uses \Guard\Config\Schema
 * @uses \Guard\Config\Validation\DirectoryConfigScalarReader
 * @uses \Guard\Config\Validation\DirectoryConfigStringListReader
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Config\Value\StructureConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Config\Reader\DirectoryPolicyReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\Reader\DirectoryRuleConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\DirectoryRuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Config\Validation\DirectoryConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Validation\DirectoryConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Value\DirectoryRuleConfig::class)]
#[UsesClass(\Guard\Config\Value\StructureConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class DirectoryPolicyReaderTest extends TestCase
{
    public function testReadRetainsAllDirectoryRules(): void
    {
        $config = (new \Guard\Config\Reader\DirectoryPolicyReader())->read(['directories' => [['path' => '**', 'max_files' => 15, 'deny' => ['*Helper.php']]]], '/project');
        self::assertSame(15, $config->rules[0]->maxFiles);
        self::assertSame(['*Helper.php'], $config->rules[0]->deny);
    }

}
