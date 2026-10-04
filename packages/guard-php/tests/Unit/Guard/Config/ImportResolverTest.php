<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Config\ImportResolver
 * @uses \Toolkit\Guard\Config\DocumentMerger
 * @uses \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Config\ImportResolver::class)]
#[UsesClass(\Toolkit\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class ImportResolverTest extends TestCase
{
    public function testResolveOverlaysTheProjectFileOnImports(): void
    {
        $root = sys_get_temp_dir() . '/guard-resolve-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/preset.yaml', "quality:\n  profiles:\n    standard:\n      limits:\n        file: {lines: 500, ncloc: 350}\n  default: standard\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [preset.yaml]\nquality:\n  profiles:\n    standard:\n      limits:\n        file: {lines: 800}\n");
        $data = (new \Toolkit\Guard\Config\ImportResolver())->resolve($root . '/guard.yaml');
        self::assertSame(['profiles' => ['standard' => ['limits' => ['file' => ['lines' => 800, 'ncloc' => 350]]]], 'default' => 'standard'], $data['quality']);
        self::assertArrayNotHasKey('imports', $data);
    }

    public function testResolveRejectsAMissingImport(): void
    {
        $root = sys_get_temp_dir() . '/guard-missing-import-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [missing.yaml]\n");
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Guard import not found');
        (new \Toolkit\Guard\Config\ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testResolveRejectsACircularImport(): void
    {
        $root = sys_get_temp_dir() . '/guard-cycle-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/a.yaml', "imports: [b.yaml]\n");
        file_put_contents($root . '/b.yaml', "imports: [a.yaml]\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [a.yaml]\n");
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Circular guard import');
        (new \Toolkit\Guard\Config\ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testResolveRejectsDuplicateIdsInOneFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-duplicate-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\nconfiguration:\n  - {id: mode, file: a.json, select: /mode, assert: {equals: A}}\n  - {id: mode, file: b.json, select: /mode, assert: {equals: B}}\n");
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Duplicate configuration rule "mode"');
        (new \Toolkit\Guard\Config\ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testVersionRejectsANewerImportedPreset(): void
    {
        $root = sys_get_temp_dir() . '/guard-import-version-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/preset.yaml', "version: 2\nconfiguration: []\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [preset.yaml]\n");
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('preset.yaml.version must be 1');
        (new \Toolkit\Guard\Config\ImportResolver())->resolve($root . '/guard.yaml');
    }

    public function testDocumentReadsAFileThatImportsNothing(): void
    {
        $root = sys_get_temp_dir() . '/guard-document-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        self::assertSame(['version' => 1], (new \Toolkit\Guard\Config\ImportResolver())->document($root . '/guard.yaml', true));
    }

    public function testMappingRejectsUnknownKeys(): void
    {
        $root = sys_get_temp_dir() . '/guard-mapping-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\nnope: true\n");
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('unknown key "nope"');
        (new \Toolkit\Guard\Config\ImportResolver())->mapping($root . '/guard.yaml', true);
    }

    public function testVersionRequiresTheProjectFile(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('guard.yaml.version must be 1');
        (new \Toolkit\Guard\Config\ImportResolver())->version([], 'guard.yaml', true);
    }

    public function testAssertUniqueRejectsARepeatedId(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Duplicate configuration rule "mode"');
        (new \Toolkit\Guard\Config\ImportResolver())->assertUnique([
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
        $paths = (new \Toolkit\Guard\Config\ImportResolver())->imports(['preset.yaml', '/tmp/absolute.yaml'], $root . '/guard.yaml', true);
        self::assertSame([$root . '/preset.yaml', '/tmp/absolute.yaml'], $paths);
    }
}
