<?php

declare(strict_types=1);

namespace Tests\Unit\Repair;

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
#[CoversClass(\Guard\Repair\AtomicWriter::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
final class AtomicWriterTest extends TestCase
{
    public function testApplyPreservesPermissionsAndWritesTheReplacement(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-write-');
        self::assertIsString($path);
        file_put_contents($path, 'before');
        chmod($path, 0600);
        (new \Guard\Repair\AtomicWriter())->apply([new \Guard\Policy\FileChange($path, 'before', 'after')]);
        self::assertSame('after', file_get_contents($path));
        self::assertSame(0600, fileperms($path) & 0777);
    }

    public function testVerifyRejectsConcurrentEditsBeforeWritingAnyFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-write-');
        self::assertIsString($path);
        file_put_contents($path, 'someone else');
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Repair\AtomicWriter())->apply([new \Guard\Policy\FileChange($path, 'before', 'after')]);
    }

    public function testWriteReplacesARegularFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-atomic-');
        self::assertIsString($path);
        file_put_contents($path, 'A');
        (new \Guard\Repair\AtomicWriter())->write(new \Guard\Policy\FileChange($path, 'A', 'B'));
        self::assertSame('B', file_get_contents($path));
    }
}
