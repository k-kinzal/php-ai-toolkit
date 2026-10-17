<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Guard\Config\Configuration;
use Guard\Config\DocumentMerger;
use Guard\Config\ImportResolver;
use Guard\Config\Reader\ExtensionConfigReader;
use Guard\Config\Schema;
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
 * @covers \Guard\Config\ImportResolver
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\Reader\ExtensionConfigReader
 * @uses \Guard\Config\Schema
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(ImportResolver::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(DocumentMerger::class)]
#[UsesClass(ExtensionConfigReader::class)]
#[UsesClass(Schema::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class ImportResolverTest extends TestCase
{
    public function testResolveOverlaysTheProjectFileOnImports(): void
    {
        $root = sys_get_temp_dir() . '/guard-resolve-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/preset.yaml', "metrics:\n  source: [lib]\n  exclude: [vendor]\n  profiles:\n    standard:\n      limits:\n        file: {lines: 500, ncloc: 350}\n  default: standard\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [preset.yaml]\nmetrics:\n  source: [src]\n  profiles:\n    standard:\n      limits:\n        file: {lines: 800}\n");
        $data = (new ImportResolver())->resolve($root . '/guard.yaml');
        self::assertSame(['source' => ['src'], 'exclude' => ['vendor'], 'profiles' => ['standard' => ['limits' => ['file' => ['lines' => 800, 'ncloc' => 350]]]], 'default' => 'standard'], $data['metrics']);
        self::assertArrayNotHasKey('imports', $data);
    }

    public function testResolveRejectsAMissingImport(): void
    {
        $root = sys_get_temp_dir() . '/guard-missing-import-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [missing.yaml]\n");
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Guard import not found');
        (new ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testResolveRejectsACircularImport(): void
    {
        $root = sys_get_temp_dir() . '/guard-cycle-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/a.yaml', "imports: [b.yaml]\n");
        file_put_contents($root . '/b.yaml', "imports: [a.yaml]\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [a.yaml]\n");
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Circular guard import');
        (new ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testResolveRejectsDuplicateIdsInOneFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-duplicate-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\nconfiguration:\n  - {id: mode, file: a.json, select: /mode, assert: {equals: A}}\n  - {id: mode, file: b.json, select: /mode, assert: {equals: B}}\n");
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Duplicate configuration rule "mode"');
        (new ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testVersionRejectsANewerImportedPreset(): void
    {
        $root = sys_get_temp_dir() . '/guard-import-version-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/preset.yaml', "version: 2\nconfiguration: []\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [preset.yaml]\n");
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('preset.yaml.version must be 1');
        (new ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testDocumentReadsAFileThatImportsNothing(): void
    {
        $root = sys_get_temp_dir() . '/guard-document-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        self::assertSame(['version' => 1], (new ImportResolver())->document($root . '/guard.yaml', true));
    }

    public function testMappingRejectsUnknownKeys(): void
    {
        $root = sys_get_temp_dir() . '/guard-mapping-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\nnope: true\n");
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('unknown key "nope"');
        (new ImportResolver())->mapping($root . '/guard.yaml', true);
    }

    public function testVersionRequiresTheProjectFile(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('guard.yaml.version must be 1');
        (new ImportResolver())->version([], 'guard.yaml', true);
    }

    public function testAssertUniqueRejectsARepeatedId(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Duplicate configuration rule "mode"');
        (new ImportResolver())->assertUnique([
            'configuration' => [
                ['id' => 'mode', 'file' => 'a.json'],
                ['id' => 'mode', 'file' => 'b.json'],
            ],
        ], '/tmp/guard.yaml');
    }

    public function testImportsResolvesPathsFromTheListingFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-imports-' . uniqid();
        mkdir($root);
        $paths = (new ImportResolver())->imports(['preset.yaml', '/tmp/absolute.yaml'], $root . '/guard.yaml', true);
        self::assertSame([$root . '/preset.yaml', '/tmp/absolute.yaml'], $paths);
    }
}
