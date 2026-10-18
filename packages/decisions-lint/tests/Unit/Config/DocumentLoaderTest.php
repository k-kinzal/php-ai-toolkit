<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use DecisionsLint\Config\DocumentLoader;
use DecisionsLint\Config\SchemaCatalog;
use DecisionsLint\Config\SchemaValidator;
use DecisionsLint\Diagnostic\LintException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Config\DocumentLoader
 * @uses \DecisionsLint\Config\SchemaCatalog
 * @uses \DecisionsLint\Config\SchemaValidator
 * @uses \DecisionsLint\Diagnostic\LintException
 */
#[CoversClass(DocumentLoader::class)]
#[UsesClass(SchemaCatalog::class)]
#[UsesClass(SchemaValidator::class)]
#[UsesClass(LintException::class)]
final class DocumentLoaderTest extends TestCase
{
    public function testLoadReturnsAValidDocumentAsAMapping(): void
    {
        $path = sys_get_temp_dir() . '/decisions-' . uniqid() . '.yaml';
        file_put_contents($path, "version: 1\npaths: [src]\n");
        try {
            self::assertSame(['version' => 1, 'paths' => ['src']], (new DocumentLoader(new SchemaValidator()))->load($path, SchemaCatalog::DECISIONS, 'decisions.yaml'));
        } finally {
            unlink($path);
        }
    }

    public function testLoadRejectsAMissingConfiguration(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Configuration not found: /nonexistent/decisions.yaml. Run decisions-lint init to create it, or pass --config.');
        (new DocumentLoader())->load('/nonexistent/decisions.yaml', SchemaCatalog::DECISIONS, 'decisions.yaml');
    }

    public function testLoadRejectsAMissingRuleFileWithTheImportHint(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Rule file not found: /nonexistent/a.yaml. Check that the path is relative to the file that imports it.');
        (new DocumentLoader())->load('/nonexistent/a.yaml', SchemaCatalog::RULES, 'a.yaml');
    }

    public function testLoadRejectsInvalidYaml(): void
    {
        $path = sys_get_temp_dir() . '/decisions-' . uniqid() . '.yaml';
        file_put_contents($path, "version: 1\npaths: [src\n");
        try {
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('decisions.yaml is not valid YAML:');
            (new DocumentLoader())->load($path, SchemaCatalog::DECISIONS, 'decisions.yaml');
        } finally {
            unlink($path);
        }
    }

    public function testLoadRejectsADocumentThatDepartsFromTheSchema(): void
    {
        $path = sys_get_temp_dir() . '/decisions-' . uniqid() . '.yaml';
        file_put_contents($path, "version: 1\nrules: [a]\n");
        try {
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('rules/x.yaml: rules must be a mapping, not a list.');
            (new DocumentLoader())->load($path, SchemaCatalog::RULES, 'rules/x.yaml');
        } finally {
            unlink($path);
        }
    }
}
