<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use Guard\Collect\Filesystem\QueryResult;
use Guard\Config\Configuration;
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
#[CoversClass(QueryResult::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class QueryResultTest extends TestCase
{
    public function testFileSetRestoresSelectionOrderAfterPhysicalFileGrouping(): void
    {
        $result = new QueryResult();
        $a = new FileRecord('/root/A', 'A', new Entry(false, true, false, '/root/A'));
        $b = new FileRecord('/root/B', 'B', new Entry(false, true, false, '/root/B'));
        $result->addFile($b);
        $result->addFile($a);
        $listing = new DirectoryListing('src', ['A', 'B'], []);
        $result->addDirectory($listing);
        $values = ['B' => new StructuredFile($b, true, null, null), 'A' => new StructuredFile($a, true, null, null)];
        $set = $result->fileSet($values);
        self::assertSame(['A', 'B'], array_keys($set->files));
        self::assertSame($values['A'], $set->files['A']);
        self::assertSame(['src' => $listing], $set->directories);
    }
    /**

     */
    public function testAddFileDeduplicatesOverlappingRequestsByRelativePath(): void
    {
        $result = new QueryResult();
        $file = new FileRecord('/root/A', 'A', new Entry(false, true, false, '/root/A'));
        $result->addFile($file);
        $result->addFile($file);
        self::assertSame(['A' => $file], $result->files());
    }
    /**

     */
    public function testFilesReturnsTheEstablishedSortedOrder(): void
    {
        $result = new QueryResult();
        $result->addFile(new FileRecord('/root/B', 'B', new Entry(false, true, false, '/root/B')));
        $result->addFile(new FileRecord('/root/A', 'A', new Entry(false, true, false, '/root/A')));
        self::assertSame(['A', 'B'], array_keys($result->files()));
    }
    /**

     */
    public function testAddDirectoryKeepsOnlyOneListingForOverlappingRoots(): void
    {
        $result = new QueryResult();
        $listing = new DirectoryListing('src', ['A.php'], []);
        $result->addDirectory($listing);
        $result->addDirectory($listing);
        self::assertSame(['src' => $listing], $result->directories());
    }
    /**

     */
    public function testDirectoriesSortsListingsIndependentlyOfTraversalOrder(): void
    {
        $result = new QueryResult();
        $result->addDirectory(new DirectoryListing('z', [], []));
        $result->addDirectory(new DirectoryListing('a', [], []));
        self::assertSame(['a', 'z'], array_keys($result->directories()));
    }
    /**

     */
    public function testFailPreservesTheFirstSelectionError(): void
    {
        $result = new QueryResult();
        $first = new PolicyException('First');
        $result->fail($first);
        $result->fail(new PolicyException('Second'));
        self::assertSame($first, $result->failure());
    }
    /**

     */
    public function testFailureIsAbsentForSuccessfulSelections(): void
    {
        self::assertNull((new QueryResult())->failure());
    }
}
