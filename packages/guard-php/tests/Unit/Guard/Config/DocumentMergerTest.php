<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Config\DocumentMerger
 */
#[CoversClass(\Toolkit\Guard\Config\DocumentMerger::class)]
final class DocumentMergerTest extends TestCase
{
    public function testMergeReplacesScopeAndPatchesConfiguration(): void
    {
        $merged = (new \Toolkit\Guard\Config\DocumentMerger())->merge(
            [
                'scope' => ['source' => ['lib'], 'exclude' => ['vendor']],
                'configuration' => [['id' => 'mode', 'file' => 'app.json', 'assert' => ['equals' => 'A']]],
            ],
            [
                'scope' => ['source' => ['src']],
                'configuration' => [['id' => 'mode', 'file' => 'settings.json'], ['id' => 'workers', 'file' => 'app.json']],
            ],
        );
        self::assertSame(['source' => ['src']], $merged['scope']);
        self::assertSame([
            ['id' => 'mode', 'file' => 'settings.json', 'assert' => ['equals' => 'A']],
            ['id' => 'workers', 'file' => 'app.json'],
        ], $merged['configuration']);
    }

    public function testMergeSectionReplacesANonMapping(): void
    {
        self::assertSame(['quality' => 'bad'], (new \Toolkit\Guard\Config\DocumentMerger())->mergeSection([], ['quality' => 'bad'], 'quality'));
    }

    public function testQualityOverrideReplacesOneMetric(): void
    {
        $quality = (new \Toolkit\Guard\Config\DocumentMerger())->quality(
            ['profiles' => ['standard' => ['limits' => ['file' => ['lines' => 500, 'ncloc' => 350]]]], 'default' => 'standard'],
            ['profiles' => ['standard' => ['limits' => ['file' => ['lines' => 800]]]]],
        );
        self::assertSame([
            'profiles' => ['standard' => ['limits' => ['file' => ['lines' => 800, 'ncloc' => 350]]]],
            'default' => 'standard',
        ], $quality);
    }

    public function testProfilesAddsANamedProfile(): void
    {
        $profiles = (new \Toolkit\Guard\Config\DocumentMerger())->profiles(
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
        $profile = (new \Toolkit\Guard\Config\DocumentMerger())->profile(
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
            (new \Toolkit\Guard\Config\DocumentMerger())->limits(['file' => ['lines' => 500, 'ncloc' => 350]], ['file' => ['lines' => 800]]),
        );
    }

    public function testStructureOverrideReplacesOneDirectoryKey(): void
    {
        $structure = (new \Toolkit\Guard\Config\DocumentMerger())->structure(
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
        $documentation = (new \Toolkit\Guard\Config\DocumentMerger())->documentation(
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
            (new \Toolkit\Guard\Config\DocumentMerger())->files(
                ['README.md' => ['headings' => ['# Old']], 'guide.md' => ['headings' => ['# Guide']]],
                ['README.md' => ['headings' => ['# New']]],
            ),
        );
    }

    public function testEntriesPatchesByIdentity(): void
    {
        self::assertSame(
            [['id' => 'mode', 'file' => 'settings.json']],
            (new \Toolkit\Guard\Config\DocumentMerger())->entries([['id' => 'mode', 'file' => 'app.json']], [['id' => 'mode', 'file' => 'settings.json']], 'id'),
        );
    }

    public function testIndexKeepsTheFirstPosition(): void
    {
        self::assertSame([[['id' => 'mode']], ['mode' => 0]], (new \Toolkit\Guard\Config\DocumentMerger())->index([['id' => 'mode']], 'id'));
    }

    public function testEntryNameReturnsNullWithoutAnIdentity(): void
    {
        self::assertNull((new \Toolkit\Guard\Config\DocumentMerger())->entryName(['file' => 'a.json'], 'id'));
        self::assertSame('mode', (new \Toolkit\Guard\Config\DocumentMerger())->entryName(['id' => 'mode'], 'id'));
    }

    public function testPatchReplacesListedKeys(): void
    {
        self::assertSame(
            ['file' => 'b.json', 'level' => 'required'],
            (new \Toolkit\Guard\Config\DocumentMerger())->patch(['file' => 'a.json', 'level' => 'required'], ['file' => 'b.json']),
        );
    }

    public function testIsMappingAcceptsMapsAndRejectsLists(): void
    {
        self::assertTrue((new \Toolkit\Guard\Config\DocumentMerger())->isMapping(['file' => 'a.json']));
        self::assertFalse((new \Toolkit\Guard\Config\DocumentMerger())->isMapping(['a.json']));
    }

    public function testIsListAcceptsSequences(): void
    {
        self::assertTrue((new \Toolkit\Guard\Config\DocumentMerger())->isList(['a.json']));
        self::assertFalse((new \Toolkit\Guard\Config\DocumentMerger())->isList(['file' => 'a.json']));
    }
}
