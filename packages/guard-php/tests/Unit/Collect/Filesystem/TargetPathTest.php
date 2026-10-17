<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use Guard\Collect\Filesystem\NativeFilesystem;
use Guard\Collect\Filesystem\Snapshot;
use Guard\Collect\Filesystem\TargetPath;
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
 * @covers \Guard\Collect\Filesystem\TargetPath
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Collect\Filesystem\NativeFilesystem
 * @uses \Guard\Collect\Filesystem\Snapshot
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
#[CoversClass(TargetPath::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(NativeFilesystem::class)]
#[UsesClass(Snapshot::class)]
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
final class TargetPathTest extends TestCase
{
    public function testResolveRejectsEscapingRoot(): void
    {
        $this->expectException(PolicyException::class);
        (new TargetPath())->resolve(sys_get_temp_dir(), '../outside.json');
    }

    public function testRejectsSymlinkedTargets(): void
    {
        $root = sys_get_temp_dir() . '/guard-path-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/actual.json', '{}');
        symlink($root . '/actual.json', $root . '/linked.json');
        $this->expectException(PolicyException::class);
        (new TargetPath())->resolve($root, 'linked.json');
    }

    public function testResolveRejectsAMissingTarget(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Target "missing.json" does not exist. Create the configuration file first.');
        (new TargetPath())->resolve(sys_get_temp_dir() . '/guard-path-' . uniqid(), 'missing.json');
    }

    public function testConfineAcceptsAMissingTargetInsideTheRoot(): void
    {
        $root = sys_get_temp_dir() . '/guard-path-' . uniqid();
        mkdir($root);
        try {
            self::assertSame($root . '/config/missing.json', (new TargetPath())->confine($root, './config//missing.json'));
        } finally {
            rmdir($root);
        }
    }

    public function testConfineRejectsEscapingRoot(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Target "../outside.json" must stay inside the directory containing guard.yaml.');
        (new TargetPath())->confine(sys_get_temp_dir(), '../outside.json');
    }
}
