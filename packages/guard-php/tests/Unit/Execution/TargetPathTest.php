<?php

declare(strict_types=1);

namespace Tests\Unit\Execution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Execution\TargetPath
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Filesystem\NativeFilesystem
 * @uses \Guard\Collect\Filesystem\Snapshot
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
#[CoversClass(\Guard\Execution\TargetPath::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Filesystem\NativeFilesystem::class)]
#[UsesClass(\Guard\Collect\Filesystem\Snapshot::class)]
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
final class TargetPathTest extends TestCase
{
    public function testResolveRejectsEscapingRoot(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Execution\TargetPath())->resolve(sys_get_temp_dir(), '../outside.json');
    }

    public function testRejectsSymlinkedTargets(): void
    {
        $root = sys_get_temp_dir() . '/guard-path-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/actual.json', '{}');
        symlink($root . '/actual.json', $root . '/linked.json');
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Execution\TargetPath())->resolve($root, 'linked.json');
    }

    public function testResolveRejectsAMissingTarget(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Target "missing.json" does not exist. Create the configuration file first.');
        (new \Guard\Execution\TargetPath())->resolve(sys_get_temp_dir() . '/guard-path-' . uniqid(), 'missing.json');
    }

    public function testConfineAcceptsAMissingTargetInsideTheRoot(): void
    {
        $root = sys_get_temp_dir() . '/guard-path-' . uniqid();
        mkdir($root);
        try {
            self::assertSame($root . '/config/missing.json', (new \Guard\Execution\TargetPath())->confine($root, './config//missing.json'));
        } finally {
            rmdir($root);
        }
    }

    public function testConfineRejectsEscapingRoot(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Target "../outside.json" must stay inside the directory containing guard.yaml.');
        (new \Guard\Execution\TargetPath())->confine(sys_get_temp_dir(), '../outside.json');
    }
}
