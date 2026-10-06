<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Filesystem\QueryResult
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Collect\Filesystem\QueryResult::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class QueryResultTest extends TestCase
{
    public function testFileSetRestoresSelectionOrderAfterPhysicalFileGrouping(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $a = new \Guard\Collect\FileRecord('/root/A', 'A', new \Guard\Collect\Filesystem\Entry(false, true, false, '/root/A'));
        $b = new \Guard\Collect\FileRecord('/root/B', 'B', new \Guard\Collect\Filesystem\Entry(false, true, false, '/root/B'));
        $result->addFile($b);
        $result->addFile($a);
        $listing = new \Guard\Collect\DirectoryListing('src', ['A', 'B'], []);
        $result->addDirectory($listing);
        $values = ['B' => new \Guard\Collect\StructuredFile($b, true, null, null), 'A' => new \Guard\Collect\StructuredFile($a, true, null, null)];
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
        $file = new \Guard\Collect\FileRecord('/root/A', 'A', new \Guard\Collect\Filesystem\Entry(false, true, false, '/root/A'));
        $result->addFile($file);
        $result->addFile($file);
        self::assertSame(['A' => $file], $result->files());
    }
    /**

     */
    public function testFilesReturnsTheEstablishedSortedOrder(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $result->addFile(new \Guard\Collect\FileRecord('/root/B', 'B', new \Guard\Collect\Filesystem\Entry(false, true, false, '/root/B')));
        $result->addFile(new \Guard\Collect\FileRecord('/root/A', 'A', new \Guard\Collect\Filesystem\Entry(false, true, false, '/root/A')));
        self::assertSame(['A', 'B'], array_keys($result->files()));
    }
    /**

     */
    public function testAddDirectoryKeepsOnlyOneListingForOverlappingRoots(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $listing = new \Guard\Collect\DirectoryListing('src', ['A.php'], []);
        $result->addDirectory($listing);
        $result->addDirectory($listing);
        self::assertSame(['src' => $listing], $result->directories());
    }
    /**

     */
    public function testDirectoriesSortsListingsIndependentlyOfTraversalOrder(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $result->addDirectory(new \Guard\Collect\DirectoryListing('z', [], []));
        $result->addDirectory(new \Guard\Collect\DirectoryListing('a', [], []));
        self::assertSame(['a', 'z'], array_keys($result->directories()));
    }
    /**

     */
    public function testFailPreservesTheFirstSelectionError(): void
    {
        $result = new \Guard\Collect\Filesystem\QueryResult();
        $first = new \Guard\Policy\PolicyException('First');
        $result->fail($first);
        $result->fail(new \Guard\Policy\PolicyException('Second'));
        self::assertSame($first, $result->failure());
    }
    /**

     */
    public function testFailureIsAbsentForSuccessfulSelections(): void
    {
        self::assertNull((new \Guard\Collect\Filesystem\QueryResult())->failure());
    }
}
