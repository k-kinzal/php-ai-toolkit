<?php

declare(strict_types=1);

namespace Tests\Unit\Execution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Execution\AtomicWriter
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
#[CoversClass(\Guard\Execution\AtomicWriter::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class AtomicWriterTest extends TestCase
{
    public function testApplyPreservesPermissionsAndWritesTheReplacement(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-write-');
        self::assertIsString($path);
        file_put_contents($path, 'before');
        chmod($path, 0600);
        (new \Guard\Execution\AtomicWriter())->apply([new \Guard\Execution\FileChange($path, 'before', 'after')]);
        self::assertSame('after', file_get_contents($path));
        self::assertSame(0600, fileperms($path) & 0777);
    }

    public function testVerifyRejectsConcurrentEditsBeforeWritingAnyFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-write-');
        self::assertIsString($path);
        file_put_contents($path, 'someone else');
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Execution\AtomicWriter())->apply([new \Guard\Execution\FileChange($path, 'before', 'after')]);
    }

    public function testWriteReplacesARegularFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-atomic-');
        self::assertIsString($path);
        file_put_contents($path, 'A');
        (new \Guard\Execution\AtomicWriter())->write(new \Guard\Execution\FileChange($path, 'A', 'B'));
        self::assertSame('B', file_get_contents($path));
    }
}
