<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Configuration;
use Guard\Config\Reader\ScopeConfigReader;
use Guard\Config\Schema;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Scope;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\ScopeConfigReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Scope
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Schema
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(ScopeConfigReader::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Scope::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class ScopeConfigReaderTest extends TestCase
{
    public function testReadKeepsAnEmptyIncludeListEmpty(): void
    {
        self::assertSame([], (new ScopeConfigReader())->read(['include' => []])->include);
        self::assertSame(['**'], (new ScopeConfigReader())->read([])->include);
    }

    public function testPathsAcceptsRelativePatternsAndDotSpellings(): void
    {
        self::assertSame(['./src/**/*.php', 'README.md'], (new ScopeConfigReader())->paths(['./src/**/*.php', 'README.md'], 'collect.include'));
    }

    /**
     * @dataProvider providerInvalidScopes
     */
    #[DataProvider('providerInvalidScopes')]
    public function testReadRejectsInvalidBoundaries(mixed $scope): void
    {
        $this->expectException(PolicyException::class);
        (new ScopeConfigReader())->read($scope);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function providerInvalidScopes(): iterable
    {
        yield 'null' => [null];
        yield 'list' => [['src']];
        yield 'unknown key' => [['includes' => ['src']]];
        yield 'null include' => [['include' => null]];
        yield 'null exclude' => [['exclude' => null]];
        yield 'string' => [['include' => 'src']];
        yield 'absolute' => [['include' => ['/tmp']]];
        yield 'parent' => [['exclude' => ['src/../vendor']]];
        yield 'windows' => [['include' => ['C:/src']]];
        yield 'backslash' => [['include' => ['src\\file.php']]];
    }
}
