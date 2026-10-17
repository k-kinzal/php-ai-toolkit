<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Guard\Config\Configuration;
use Guard\Config\DocumentMerger;
use Guard\Config\Reader\ExtensionConfigReader;
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
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\DocumentMerger
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\ExtensionConfigReader
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(DocumentMerger::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ExtensionConfigReader::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class DocumentMergerTest extends TestCase
{
    public function testMergeReplacesMetricSourceAndPatchesConfiguration(): void
    {
        $merged = (new DocumentMerger())->merge(
            [
                'metrics' => ['source' => ['lib'], 'exclude' => ['vendor']],
                'configuration' => [['id' => 'mode', 'file' => 'app.json', 'assert' => ['equals' => 'A']]],
            ],
            [
                'metrics' => ['source' => ['src']],
                'configuration' => [['id' => 'mode', 'file' => 'settings.json'], ['id' => 'workers', 'file' => 'app.json']],
            ],
        );
        self::assertSame(['source' => ['src'], 'exclude' => ['vendor']], $merged['metrics']);
        self::assertSame([
            ['id' => 'mode', 'file' => 'settings.json', 'assert' => ['equals' => 'A']],
            ['id' => 'workers', 'file' => 'app.json'],
        ], $merged['configuration']);
    }

    public function testMergeSectionReplacesANonMapping(): void
    {
        self::assertSame(['metrics' => 'bad'], (new DocumentMerger())->mergeSection([], ['metrics' => 'bad'], 'metrics'));
    }

    public function testMetricsOverrideReplacesOneMetric(): void
    {
        $metrics = (new DocumentMerger())->metrics(
            ['source' => ['lib'], 'profiles' => ['standard' => ['limits' => ['file' => ['lines' => 500, 'ncloc' => 350]]]], 'default' => 'standard'],
            ['source' => ['src'], 'profiles' => ['standard' => ['limits' => ['file' => ['lines' => 800]]]]],
        );
        self::assertSame([
            'source' => ['src'],
            'profiles' => ['standard' => ['limits' => ['file' => ['lines' => 800, 'ncloc' => 350]]]],
            'default' => 'standard',
        ], $metrics);
    }

    public function testProfilesAddsANamedProfile(): void
    {
        $profiles = (new DocumentMerger())->profiles(
            ['standard' => ['limits' => ['file' => ['lines' => 500]]]],
            ['native' => ['extends' => 'standard']],
        );
        self::assertSame([
            'standard' => ['limits' => ['file' => ['lines' => 500]]],
            'native' => ['extends' => 'standard'],
        ], $profiles);
    }

    public function testProfileMergesLimitMetrics(): void
    {
        $profile = (new DocumentMerger())->profile(
            ['limits' => ['file' => ['lines' => 500, 'ncloc' => 350]], 'extends' => 'base'],
            ['limits' => ['file' => ['lines' => 800]]],
        );
        self::assertSame([
            'limits' => ['file' => ['lines' => 800, 'ncloc' => 350]],
            'extends' => 'base',
        ], $profile);
    }

    public function testLimitsReplacesOneMetric(): void
    {
        self::assertSame(
            ['file' => ['lines' => 800, 'ncloc' => 350]],
            (new DocumentMerger())->limits(['file' => ['lines' => 500, 'ncloc' => 350]], ['file' => ['lines' => 800]]),
        );
    }

    public function testStructureOverrideReplacesOneDirectoryKey(): void
    {
        $structure = (new DocumentMerger())->structure(
            ['paths' => ['.'], 'directories' => [['path' => 'src/**', 'max_files' => 15, 'deny' => ['*Helper.php']]]],
            ['directories' => [['path' => 'src/**', 'max_files' => 20], ['path' => 'lib/**', 'max_files' => 10]]],
        );
        self::assertSame([
            'paths' => ['.'],
            'directories' => [
                ['path' => 'src/**', 'max_files' => 20, 'deny' => ['*Helper.php']],
                ['path' => 'lib/**', 'max_files' => 10],
            ],
        ], $structure);
    }

    public function testDocumentationReplacesOneDocument(): void
    {
        $documentation = (new DocumentMerger())->documentation(
            ['files' => ['README.md' => ['headings' => ['# Old']], 'guide.md' => ['headings' => ['# Guide']]], 'scan' => ['README.md']],
            ['files' => ['README.md' => ['headings' => ['# New']]], 'scan' => ['README.md', 'guide.md']],
        );
        self::assertSame([
            'files' => ['README.md' => ['headings' => ['# New']], 'guide.md' => ['headings' => ['# Guide']]],
            'scan' => ['README.md', 'guide.md'],
        ], $documentation);
    }

    public function testFilesReplacesOnePath(): void
    {
        self::assertSame(
            ['README.md' => ['headings' => ['# New']], 'guide.md' => ['headings' => ['# Guide']]],
            (new DocumentMerger())->files(
                ['README.md' => ['headings' => ['# Old']], 'guide.md' => ['headings' => ['# Guide']]],
                ['README.md' => ['headings' => ['# New']]],
            ),
        );
    }

    public function testFilesKeepsImportedKeysThatALaterDeclarationOmits(): void
    {
        self::assertSame(
            ['README.md' => ['outlines' => ['package' => ['# *']], 'badges' => [['name' => 'PHP', 'image' => 'p']], 'headings' => ['# Tool']]],
            (new DocumentMerger())->files(
                ['README.md' => ['outlines' => ['package' => ['# *']], 'badges' => [['name' => 'PHP', 'image' => 'p']]]],
                ['README.md' => ['headings' => ['# Tool']]],
            ),
        );
        self::assertSame(['CLAUDE.md' => 'invalid'], (new DocumentMerger())->files(['CLAUDE.md' => ['content' => '@AGENTS.md']], ['CLAUDE.md' => 'invalid']));
    }

    public function testEntriesPatchesByIdentity(): void
    {
        self::assertSame(
            [['id' => 'mode', 'file' => 'settings.json']],
            (new DocumentMerger())->entries([['id' => 'mode', 'file' => 'app.json']], [['id' => 'mode', 'file' => 'settings.json']], 'id'),
        );
    }

    public function testIndexKeepsTheFirstPosition(): void
    {
        self::assertSame([[['id' => 'mode']], ['mode' => 0]], (new DocumentMerger())->index([['id' => 'mode']], 'id'));
    }

    public function testEntryNameReturnsNullWithoutAnIdentity(): void
    {
        self::assertNull((new DocumentMerger())->entryName(['file' => 'a.json'], 'id'));
        self::assertSame('mode', (new DocumentMerger())->entryName(['id' => 'mode'], 'id'));
    }

    public function testPatchReplacesListedKeys(): void
    {
        self::assertSame(
            ['file' => 'b.json', 'level' => 'required'],
            (new DocumentMerger())->patch(['file' => 'a.json', 'level' => 'required'], ['file' => 'b.json']),
        );
    }

    public function testIsMappingAcceptsMapsAndRejectsLists(): void
    {
        self::assertTrue((new DocumentMerger())->isMapping(['file' => 'a.json']));
        self::assertFalse((new DocumentMerger())->isMapping(['a.json']));
    }

    public function testIsListAcceptsSequences(): void
    {
        self::assertTrue((new DocumentMerger())->isList(['a.json']));
        self::assertFalse((new DocumentMerger())->isList(['file' => 'a.json']));
    }
}
