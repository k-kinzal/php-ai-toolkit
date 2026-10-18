<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use DecisionsLint\Config\DocumentLoader;
use DecisionsLint\Config\DocumentMerger;
use DecisionsLint\Config\ImportResolver;
use DecisionsLint\Config\SchemaCatalog;
use DecisionsLint\Config\SchemaValidator;
use DecisionsLint\Diagnostic\LintException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Config\ImportResolver
 * @uses \DecisionsLint\Config\DocumentLoader
 * @uses \DecisionsLint\Config\DocumentMerger
 * @uses \DecisionsLint\Config\SchemaCatalog
 * @uses \DecisionsLint\Config\SchemaValidator
 * @uses \DecisionsLint\Diagnostic\LintException
 */
#[CoversClass(ImportResolver::class)]
#[UsesClass(DocumentLoader::class)]
#[UsesClass(DocumentMerger::class)]
#[UsesClass(SchemaCatalog::class)]
#[UsesClass(SchemaValidator::class)]
#[UsesClass(LintException::class)]
final class ImportResolverTest extends TestCase
{
    public function testResolveLaysLaterImportsAndTheProjectOverTheEarlierOnes(): void
    {
        $root = sys_get_temp_dir() . '/decisions-import-' . uniqid();
        mkdir($root . '/rules', 0777, true);
        file_put_contents($root . '/rules/a.yaml', "version: 1\nrules:\n  a.one:\n    severity: warning\n    target: {kind: method}\n    decision: {type: predicate, instructions: 'Q?', threshold: 0.5}\n    violations: [{answer: false, message: m, fix: f}]\n");
        file_put_contents($root . '/rules/b.yaml', "version: 1\nrules:\n  a.one:\n    severity: info\n    target: {kind: method}\n    decision: {type: predicate, instructions: 'Q?'}\n    violations: [{answer: false, message: m, fix: f}]\n  b.two:\n    target: {kind: file}\n    decision: {type: predicate, instructions: 'F?'}\n    violations: [{answer: true, message: m, fix: f}]\n");
        file_put_contents($root . '/decisions.yaml', "version: 1\nimports: [rules/a.yaml, rules/b.yaml]\nrules:\n  a.one:\n    decision: {threshold: 0.9}\n  c.three:\n    enabled: false\n");
        try {
            $document = (new ImportResolver(new DocumentLoader(), new DocumentMerger()))->resolve($root . '/decisions.yaml');
            $rules = $document['rules'];
            self::assertIsArray($rules);
            self::assertSame(['a.one', 'b.two', 'c.three'], array_keys($rules));
            self::assertSame([
                'source' => ['rules/a.yaml', 'rules/b.yaml', 'decisions.yaml'],
                'severity' => 'info',
                'target' => ['kind' => 'method'],
                'decision' => ['type' => 'predicate', 'instructions' => 'Q?', 'threshold' => 0.9],
                'violations' => [['answer' => false, 'message' => 'm', 'fix' => 'f']],
            ], $rules['a.one']);
            self::assertSame([
                'source' => ['rules/b.yaml'],
                'target' => ['kind' => 'file'],
                'decision' => ['type' => 'predicate', 'instructions' => 'F?'],
                'violations' => [['answer' => true, 'message' => 'm', 'fix' => 'f']],
            ], $rules['b.two']);
            self::assertSame(['source' => ['decisions.yaml'], 'enabled' => false], $rules['c.three']);
        } finally {
            unlink($root . '/rules/a.yaml');
            unlink($root . '/rules/b.yaml');
            unlink($root . '/decisions.yaml');
            rmdir($root . '/rules');
            rmdir($root);
        }
    }

    public function testResolveRejectsAMissingImport(): void
    {
        $root = sys_get_temp_dir() . '/decisions-import-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/decisions.yaml', "version: 1\nimports: [rules/missing.yaml]\n");
        try {
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('Rule file not found: ' . $root . '/rules/missing.yaml. Check that the path is relative to the file that imports it.');
            (new ImportResolver())->resolve($root . '/decisions.yaml');
        } finally {
            unlink($root . '/decisions.yaml');
            rmdir($root);
        }
    }

    public function testOverlayMergesByIdAndRecordsTheSources(): void
    {
        $rules = (new ImportResolver())->overlay(['a' => ['severity' => 'warning', 'source' => ['x.yaml']]], ['a' => ['severity' => 'error'], 'b' => ['enabled' => true]], 'y.yaml');
        self::assertSame(['severity' => 'error', 'source' => ['x.yaml', 'y.yaml']], $rules['a']);
        self::assertSame(['source' => ['y.yaml'], 'enabled' => true], $rules['b']);
    }

    public function testMappingReadsMappingsAndIgnoresOtherValues(): void
    {
        self::assertSame(['a' => 1], (new ImportResolver())->mapping(['a' => 1]));
        self::assertSame([], (new ImportResolver())->mapping('x'));
    }

    public function testStringsKeepsOnlyStrings(): void
    {
        self::assertSame(['a', 'b'], (new ImportResolver())->strings(['a', 1, 'b']));
        self::assertSame([], (new ImportResolver())->strings(null));
    }
}
