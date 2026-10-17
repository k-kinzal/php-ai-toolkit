<?php

declare(strict_types=1);

namespace Tests\Unit\Repair;

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
use Guard\Repair\AtomicWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Repair\AtomicWriter
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
#[CoversClass(AtomicWriter::class)]
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
final class AtomicWriterTest extends TestCase
{
    public function testApplyPreservesPermissionsAndWritesTheReplacement(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-write-');
        self::assertIsString($path);
        file_put_contents($path, 'before');
        chmod($path, 0600);
        (new AtomicWriter())->apply([new FileChange($path, 'before', 'after')]);
        self::assertSame('after', file_get_contents($path));
        self::assertSame(0600, fileperms($path) & 0777);
    }

    public function testVerifyRejectsConcurrentEditsBeforeWritingAnyFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-write-');
        self::assertIsString($path);
        file_put_contents($path, 'someone else');
        $this->expectException(PolicyException::class);
        (new AtomicWriter())->apply([new FileChange($path, 'before', 'after')]);
    }

    public function testWriteReplacesARegularFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-atomic-');
        self::assertIsString($path);
        file_put_contents($path, 'A');
        (new AtomicWriter())->write(new FileChange($path, 'A', 'B'));
        self::assertSame('B', file_get_contents($path));
    }
}
