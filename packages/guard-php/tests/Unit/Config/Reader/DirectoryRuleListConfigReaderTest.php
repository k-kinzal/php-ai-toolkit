<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\DirectoryRuleConfigReader;
use Guard\Config\Reader\DirectoryRuleListConfigReader;
use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\DirectoryRuleListConfigReader
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
 * @uses \Guard\Config\Validation\DirectoryConfigScalarReader
 * @uses \Guard\Config\Validation\DirectoryConfigStringListReader
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(DirectoryRuleListConfigReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DirectoryRuleConfigReader::class)]
#[UsesClass(DirectoryConfigScalarReader::class)]
#[UsesClass(DirectoryConfigStringListReader::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class DirectoryRuleListConfigReaderTest extends TestCase
{
    public function testReadReturnsEmptyListForEmptyInput(): void
    {
        self::assertSame([], (new DirectoryRuleListConfigReader())->read([]));
    }

    public function testReadReadsRulesInDeclarationOrder(): void
    {
        $rules = (new DirectoryRuleListConfigReader())->read([
            ['path' => 'src', 'max_files' => 10],
            ['path' => 'tests', 'max_dirs' => 5],
        ]);

        self::assertCount(2, $rules);
        self::assertSame('src', $rules[0]->path);
        self::assertSame(10, $rules[0]->maxFiles);
        self::assertSame('tests', $rules[1]->path);
        self::assertSame(5, $rules[1]->maxDirs);
    }

    public function testReadRejectsNonListValue(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules" must be a list of mappings.');

        (new DirectoryRuleListConfigReader())->read('strict');
    }

    public function testReadReportsIndexOfInvalidRule(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[1]" must be a mapping.');

        (new DirectoryRuleListConfigReader())->read([['path' => 'src'], 'oops']);
    }
}
