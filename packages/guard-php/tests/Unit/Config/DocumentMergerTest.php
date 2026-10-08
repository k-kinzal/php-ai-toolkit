<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\CoversClass;
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
#[CoversClass(\Guard\Config\DocumentMerger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\ExtensionConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
final class DocumentMergerTest extends TestCase
{
    public function testMergeReplacesMetricSourceAndPatchesConfiguration(): void
    {
        $merged = (new \Guard\Config\DocumentMerger())->merge(
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
        self::assertSame(['metrics' => 'bad'], (new \Guard\Config\DocumentMerger())->mergeSection([], ['metrics' => 'bad'], 'metrics'));
    }

    public function testMetricsOverrideReplacesOneMetric(): void
    {
        $metrics = (new \Guard\Config\DocumentMerger())->metrics(
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
        $profiles = (new \Guard\Config\DocumentMerger())->profiles(
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
        $profile = (new \Guard\Config\DocumentMerger())->profile(
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
            (new \Guard\Config\DocumentMerger())->limits(['file' => ['lines' => 500, 'ncloc' => 350]], ['file' => ['lines' => 800]]),
        );
    }

    public function testStructureOverrideReplacesOneDirectoryKey(): void
    {
        $structure = (new \Guard\Config\DocumentMerger())->structure(
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
        $documentation = (new \Guard\Config\DocumentMerger())->documentation(
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
            (new \Guard\Config\DocumentMerger())->files(
                ['README.md' => ['headings' => ['# Old']], 'guide.md' => ['headings' => ['# Guide']]],
                ['README.md' => ['headings' => ['# New']]],
            ),
        );
    }

    public function testFilesKeepsImportedKeysThatALaterDeclarationOmits(): void
    {
        self::assertSame(
            ['README.md' => ['outlines' => ['package' => ['# *']], 'badges' => [['name' => 'PHP', 'image' => 'p']], 'headings' => ['# Tool']]],
            (new \Guard\Config\DocumentMerger())->files(
                ['README.md' => ['outlines' => ['package' => ['# *']], 'badges' => [['name' => 'PHP', 'image' => 'p']]]],
                ['README.md' => ['headings' => ['# Tool']]],
            ),
        );
        self::assertSame(['CLAUDE.md' => 'invalid'], (new \Guard\Config\DocumentMerger())->files(['CLAUDE.md' => ['content' => '@AGENTS.md']], ['CLAUDE.md' => 'invalid']));
    }

    public function testEntriesPatchesByIdentity(): void
    {
        self::assertSame(
            [['id' => 'mode', 'file' => 'settings.json']],
            (new \Guard\Config\DocumentMerger())->entries([['id' => 'mode', 'file' => 'app.json']], [['id' => 'mode', 'file' => 'settings.json']], 'id'),
        );
    }

    public function testIndexKeepsTheFirstPosition(): void
    {
        self::assertSame([[['id' => 'mode']], ['mode' => 0]], (new \Guard\Config\DocumentMerger())->index([['id' => 'mode']], 'id'));
    }

    public function testEntryNameReturnsNullWithoutAnIdentity(): void
    {
        self::assertNull((new \Guard\Config\DocumentMerger())->entryName(['file' => 'a.json'], 'id'));
        self::assertSame('mode', (new \Guard\Config\DocumentMerger())->entryName(['id' => 'mode'], 'id'));
    }

    public function testPatchReplacesListedKeys(): void
    {
        self::assertSame(
            ['file' => 'b.json', 'level' => 'required'],
            (new \Guard\Config\DocumentMerger())->patch(['file' => 'a.json', 'level' => 'required'], ['file' => 'b.json']),
        );
    }

    public function testIsMappingAcceptsMapsAndRejectsLists(): void
    {
        self::assertTrue((new \Guard\Config\DocumentMerger())->isMapping(['file' => 'a.json']));
        self::assertFalse((new \Guard\Config\DocumentMerger())->isMapping(['a.json']));
    }

    public function testIsListAcceptsSequences(): void
    {
        self::assertTrue((new \Guard\Config\DocumentMerger())->isList(['a.json']));
        self::assertFalse((new \Guard\Config\DocumentMerger())->isList(['file' => 'a.json']));
    }
}
