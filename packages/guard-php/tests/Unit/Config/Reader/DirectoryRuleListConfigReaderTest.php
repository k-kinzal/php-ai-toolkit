<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\DirectoryRuleConfigReader;
use Guard\Config\Reader\DirectoryRuleListConfigReader;
use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\DirectoryRuleConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\DirectoryRuleListConfigReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\DirectoryRuleConfigReader
 * @uses \Guard\Config\Validation\DirectoryConfigScalarReader
 * @uses \Guard\Config\Validation\DirectoryConfigStringListReader
 * @uses \Guard\Policy\Definition\DirectoryRuleConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(DirectoryRuleListConfigReader::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DirectoryRuleConfigReader::class)]
#[UsesClass(DirectoryConfigScalarReader::class)]
#[UsesClass(DirectoryConfigStringListReader::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
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
