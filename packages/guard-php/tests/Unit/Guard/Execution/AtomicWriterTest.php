<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Execution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Execution\AtomicWriter
 * @uses \Toolkit\Guard\Execution\FileChange
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Execution\AtomicWriter::class)]
#[UsesClass(\Toolkit\Guard\Execution\FileChange::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class AtomicWriterTest extends TestCase
{
    public function testApplyPreservesPermissionsAndWritesTheReplacement(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-write-');
        self::assertIsString($path);
        file_put_contents($path, 'before');
        chmod($path, 0600);
        (new \Toolkit\Guard\Execution\AtomicWriter())->apply([new \Toolkit\Guard\Execution\FileChange($path, 'before', 'after')]);
        self::assertSame('after', file_get_contents($path));
        self::assertSame(0600, fileperms($path) & 0777);
    }

    public function testVerifyRejectsConcurrentEditsBeforeWritingAnyFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-write-');
        self::assertIsString($path);
        file_put_contents($path, 'someone else');
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Execution\AtomicWriter())->apply([new \Toolkit\Guard\Execution\FileChange($path, 'before', 'after')]);
    }

    public function testWriteReplacesARegularFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-atomic-');
        self::assertIsString($path);
        file_put_contents($path, 'A');
        (new \Toolkit\Guard\Execution\AtomicWriter())->write(new \Toolkit\Guard\Execution\FileChange($path, 'A', 'B'));
        self::assertSame('B', file_get_contents($path));
    }
}
