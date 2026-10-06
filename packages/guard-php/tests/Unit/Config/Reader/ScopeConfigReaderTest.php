<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\ScopeConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\ScopeConfigReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Scope
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Schema
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ScopeConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Scope::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
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
