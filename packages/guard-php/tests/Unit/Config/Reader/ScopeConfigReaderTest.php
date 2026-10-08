<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\ScopeConfigReader;
use Guard\Diagnostic\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Scope::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
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
