<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Configuration;
use Guard\Config\Reader\DirectoryRuleConfigReader;
use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\Definition\DirectoryRuleConfig;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\DirectoryRuleConfigReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
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
#[CoversClass(DirectoryRuleConfigReader::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(DirectoryConfigScalarReader::class)]
#[UsesClass(DirectoryConfigStringListReader::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class DirectoryRuleConfigReaderTest extends TestCase
{
    public function testReadParsesAllKeys(): void
    {
        $rule = (new DirectoryRuleConfigReader())->read([
            'path' => 'src/**',
            'max_files' => 25,
            'max_dirs' => 20,
            'max_total_files' => 250,
            'max_depth' => 3,
            'allow' => ['*.php'],
            'deny' => ['*Helper.php'],
            'allow_dirs' => ['[A-Z]*'],
            'deny_dirs' => ['Helpers'],
            'require' => ['README.md'],
            'forbid_empty' => true,
            'file_case' => 'pascal',
            'dir_case' => 'kebab',
        ], 0);

        self::assertSame('src/**', $rule->path);
        self::assertSame(25, $rule->maxFiles);
        self::assertSame(20, $rule->maxDirs);
        self::assertSame(250, $rule->maxTotalFiles);
        self::assertSame(3, $rule->maxDepth);
        self::assertSame(['*.php'], $rule->allow);
        self::assertSame(['*Helper.php'], $rule->deny);
        self::assertSame(['[A-Z]*'], $rule->allowDirs);
        self::assertSame(['Helpers'], $rule->denyDirs);
        self::assertSame(['README.md'], $rule->require);
        self::assertTrue($rule->forbidEmpty);
        self::assertSame('pascal', $rule->fileCase);
        self::assertSame('kebab', $rule->dirCase);
    }

    public function testReadLeavesAbsentConstraintsUnchecked(): void
    {
        $rule = (new DirectoryRuleConfigReader())->read(['path' => 'src'], 0);

        self::assertSame('src', $rule->path);
        self::assertNull($rule->maxFiles);
        self::assertNull($rule->maxDirs);
        self::assertNull($rule->maxTotalFiles);
        self::assertNull($rule->maxDepth);
        self::assertNull($rule->allow);
        self::assertNull($rule->deny);
        self::assertNull($rule->allowDirs);
        self::assertNull($rule->denyDirs);
        self::assertNull($rule->require);
        self::assertFalse($rule->forbidEmpty);
        self::assertNull($rule->fileCase);
        self::assertNull($rule->dirCase);
    }

    public function testReadDistinguishesEmptyAllowFromAbsent(): void
    {
        $rule = (new DirectoryRuleConfigReader())->read(['path' => 'src', 'allow' => []], 0);

        self::assertSame([], $rule->allow);
        self::assertNull($rule->deny);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[1]" must be a mapping.');

        (new DirectoryRuleConfigReader())->read('src', 1);
    }

    public function testReadRejectsUnknownKey(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0]" contains unsupported key "max_file".');

        (new DirectoryRuleConfigReader())->read(['path' => 'src', 'max_file' => 25], 0);
    }

    public function testReadRejectsMissingPath(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].path" must be a non-empty string.');

        (new DirectoryRuleConfigReader())->read(['max_files' => 25], 0);
    }
}
