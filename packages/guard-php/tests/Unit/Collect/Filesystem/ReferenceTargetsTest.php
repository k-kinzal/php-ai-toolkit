<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use Guard\Collect\Filesystem\NativeFilesystem;
use Guard\Collect\Filesystem\ReferenceTargets;
use Guard\Collect\Filesystem\ScopedRoots;
use Guard\Collect\Filesystem\Snapshot;
use Guard\Collect\Matching\ScopeMatcher;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\Path;
use Guard\Input\PathPatternMatcher;
use Guard\Input\Scope;
use Guard\Structure\Markdown\Link\LinkList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Guard\Collect\Filesystem\ReferenceTargets
 * @uses \Guard\Collect\Filesystem\Snapshot
 * @uses \Guard\Collect\Filesystem\NativeFilesystem
 * @uses \Guard\Collect\Filesystem\ScopedRoots
 * @uses \Guard\Collect\Matching\ScopeMatcher
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\Path
 * @uses \Guard\Input\PathPatternMatcher
 * @uses \Guard\Input\Scope
 * @uses \Guard\Structure\Markdown\Link\LinkList
 */
#[CoversClass(ReferenceTargets::class)]
#[UsesClass(Snapshot::class)]
#[UsesClass(NativeFilesystem::class)]
#[UsesClass(ScopedRoots::class)]
#[UsesClass(ScopeMatcher::class)]
#[UsesClass(Entry::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(Path::class)]
#[UsesClass(PathPatternMatcher::class)]
#[UsesClass(Scope::class)]
#[UsesClass(LinkList::class)]
final class ReferenceTargetsTest extends TestCase
{
    public function testResolveDistinguishesMissingTargetsFromOutOfScopeAndSymlinks(): void
    {
        $root = sys_get_temp_dir() . '/guard-links-' . uniqid('', true);
        mkdir($root . '/docs', 0777, true);
        file_put_contents($root . '/file.txt', 'present');
        symlink($root . '/file.txt', $root . '/alias');
        $source = new FileRecord($root . '/docs/README.md', 'docs/README.md', new Entry(false, true, false, $root . '/docs/README.md'));
        $links = new LinkList([
            ['url' => '../file.txt', 'path' => '../file.txt', 'line' => 1],
            ['url' => 'missing.md', 'path' => 'missing.md', 'line' => 2],
            ['url' => '../../outside', 'path' => '../../outside', 'line' => 3],
            ['url' => '../alias', 'path' => '../alias', 'line' => 4],
            ['url' => '../excluded', 'path' => '../excluded', 'line' => 5],
        ]);
        try {
            $targets = (new ReferenceTargets(new Snapshot(new NativeFilesystem())))->resolve($root, $source, $links, new Scope(['**'], ['excluded']));
            self::assertSame(['../file.txt', 'missing.md'], array_keys($targets));
            self::assertTrue($targets['../file.txt']->entry->file);
            self::assertFalse($targets['missing.md']->entry->file);
            self::assertSame('docs/missing.md', $targets['missing.md']->relativePath);
        } finally {
            (new Filesystem())->remove($root);
        }
    }
}
