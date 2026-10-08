<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

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
#[CoversClass(\Guard\Collect\Filesystem\TargetPath::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Collect\Filesystem\NativeFilesystem::class)]
#[UsesClass(\Guard\Collect\Filesystem\Snapshot::class)]
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
final class TargetPathTest extends TestCase
{
    public function testResolveRejectsEscapingRoot(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Collect\Filesystem\TargetPath())->resolve(sys_get_temp_dir(), '../outside.json');
    }

    public function testRejectsSymlinkedTargets(): void
    {
        $root = sys_get_temp_dir() . '/guard-path-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/actual.json', '{}');
        symlink($root . '/actual.json', $root . '/linked.json');
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Collect\Filesystem\TargetPath())->resolve($root, 'linked.json');
    }

    public function testResolveRejectsAMissingTarget(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('Target "missing.json" does not exist. Create the configuration file first.');
        (new \Guard\Collect\Filesystem\TargetPath())->resolve(sys_get_temp_dir() . '/guard-path-' . uniqid(), 'missing.json');
    }

    public function testConfineAcceptsAMissingTargetInsideTheRoot(): void
    {
        $root = sys_get_temp_dir() . '/guard-path-' . uniqid();
        mkdir($root);
        try {
            self::assertSame($root . '/config/missing.json', (new \Guard\Collect\Filesystem\TargetPath())->confine($root, './config//missing.json'));
        } finally {
            rmdir($root);
        }
    }

    public function testConfineRejectsEscapingRoot(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('Target "../outside.json" must stay inside the directory containing guard.yaml.');
        (new \Guard\Collect\Filesystem\TargetPath())->confine(sys_get_temp_dir(), '../outside.json');
    }
}
