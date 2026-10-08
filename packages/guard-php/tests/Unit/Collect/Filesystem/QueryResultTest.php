<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Filesystem\QueryResult
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(\Guard\Collect\Filesystem\QueryResult::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
final class QueryResultTest extends TestCase
{
    public function testFileSetRestoresSelectionOrderAfterPhysicalFileGrouping(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $a = new \Guard\Input\FileRecord('/root/A', 'A', new \Guard\Input\Entry(false, true, false, '/root/A'));
        $b = new \Guard\Input\FileRecord('/root/B', 'B', new \Guard\Input\Entry(false, true, false, '/root/B'));
        $result->addFile($b);
        $result->addFile($a);
        $listing = new \Guard\Input\DirectoryListing('src', ['A', 'B'], []);
        $result->addDirectory($listing);
        $values = ['B' => new \Guard\Input\StructuredFile($b, true, null, null), 'A' => new \Guard\Input\StructuredFile($a, true, null, null)];
        $set = $result->fileSet($values);
        self::assertSame(['A', 'B'], array_keys($set->files));
        self::assertSame($values['A'], $set->files['A']);
        self::assertSame(['src' => $listing], $set->directories);
    }
    /**

     */
    public function testAddFileDeduplicatesOverlappingRequestsByRelativePath(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $file = new \Guard\Input\FileRecord('/root/A', 'A', new \Guard\Input\Entry(false, true, false, '/root/A'));
        $result->addFile($file);
        $result->addFile($file);
        self::assertSame(['A' => $file], $result->files());
    }
    /**

     */
    public function testFilesReturnsTheEstablishedSortedOrder(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $result->addFile(new \Guard\Input\FileRecord('/root/B', 'B', new \Guard\Input\Entry(false, true, false, '/root/B')));
        $result->addFile(new \Guard\Input\FileRecord('/root/A', 'A', new \Guard\Input\Entry(false, true, false, '/root/A')));
        self::assertSame(['A', 'B'], array_keys($result->files()));
    }
    /**

     */
    public function testAddDirectoryKeepsOnlyOneListingForOverlappingRoots(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $listing = new \Guard\Input\DirectoryListing('src', ['A.php'], []);
        $result->addDirectory($listing);
        $result->addDirectory($listing);
        self::assertSame(['src' => $listing], $result->directories());
    }
    /**

     */
    public function testDirectoriesSortsListingsIndependentlyOfTraversalOrder(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $result->addDirectory(new \Guard\Input\DirectoryListing('z', [], []));
        $result->addDirectory(new \Guard\Input\DirectoryListing('a', [], []));
        self::assertSame(['a', 'z'], array_keys($result->directories()));
    }
    /**

     */
    public function testFailPreservesTheFirstSelectionError(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $first = new \Guard\Diagnostic\PolicyException('First');
        $result->fail($first);
        $result->fail(new \Guard\Diagnostic\PolicyException('Second'));
        self::assertSame($first, $result->failure());
    }
    /**

     */
    public function testFailureIsAbsentForSuccessfulSelections(): void
    {
        self::assertNull((new \Guard\Collect\Filesystem\QueryResult())->failure());
    }
}
