<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

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
#[CoversClass(\Guard\Config\ImportResolver::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Guard\Config\Reader\ExtensionConfigReader::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
final class ImportResolverTest extends TestCase
{
    public function testResolveOverlaysTheProjectFileOnImports(): void
    {
        $root = sys_get_temp_dir() . '/guard-resolve-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/preset.yaml', "metrics:\n  source: [lib]\n  exclude: [vendor]\n  profiles:\n    standard:\n      limits:\n        file: {lines: 500, ncloc: 350}\n  default: standard\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [preset.yaml]\nmetrics:\n  source: [src]\n  profiles:\n    standard:\n      limits:\n        file: {lines: 800}\n");
        $data = (new \Guard\Config\ImportResolver())->resolve($root . '/guard.yaml');
        self::assertSame(['source' => ['src'], 'exclude' => ['vendor'], 'profiles' => ['standard' => ['limits' => ['file' => ['lines' => 800, 'ncloc' => 350]]]], 'default' => 'standard'], $data['metrics']);
        self::assertArrayNotHasKey('imports', $data);
    }

    public function testResolveRejectsAMissingImport(): void
    {
        $root = sys_get_temp_dir() . '/guard-missing-import-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [missing.yaml]\n");
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('Guard import not found');
        (new \Guard\Config\ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testResolveRejectsACircularImport(): void
    {
        $root = sys_get_temp_dir() . '/guard-cycle-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/a.yaml', "imports: [b.yaml]\n");
        file_put_contents($root . '/b.yaml', "imports: [a.yaml]\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [a.yaml]\n");
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('Circular guard import');
        (new \Guard\Config\ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testResolveRejectsDuplicateIdsInOneFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-duplicate-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\nconfiguration:\n  - {id: mode, file: a.json, select: /mode, assert: {equals: A}}\n  - {id: mode, file: b.json, select: /mode, assert: {equals: B}}\n");
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('Duplicate configuration rule "mode"');
        (new \Guard\Config\ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testVersionRejectsANewerImportedPreset(): void
    {
        $root = sys_get_temp_dir() . '/guard-import-version-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/preset.yaml', "version: 2\nconfiguration: []\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [preset.yaml]\n");
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('preset.yaml.version must be 1');
        (new \Guard\Config\ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testDocumentReadsAFileThatImportsNothing(): void
    {
        $root = sys_get_temp_dir() . '/guard-document-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        self::assertSame(['version' => 1], (new \Guard\Config\ImportResolver())->document($root . '/guard.yaml', true));
    }

    public function testMappingRejectsUnknownKeys(): void
    {
        $root = sys_get_temp_dir() . '/guard-mapping-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\nnope: true\n");
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('unknown key "nope"');
        (new \Guard\Config\ImportResolver())->mapping($root . '/guard.yaml', true);
    }

    public function testVersionRequiresTheProjectFile(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('guard.yaml.version must be 1');
        (new \Guard\Config\ImportResolver())->version([], 'guard.yaml', true);
    }

    public function testAssertUniqueRejectsARepeatedId(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('Duplicate configuration rule "mode"');
        (new \Guard\Config\ImportResolver())->assertUnique([
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
        $paths = (new \Guard\Config\ImportResolver())->imports(['preset.yaml', '/tmp/absolute.yaml'], $root . '/guard.yaml', true);
        self::assertSame([$root . '/preset.yaml', '/tmp/absolute.yaml'], $paths);
    }
}
