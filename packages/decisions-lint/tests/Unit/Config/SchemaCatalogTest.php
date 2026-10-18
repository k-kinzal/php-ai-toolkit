<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use DecisionsLint\Config\SchemaCatalog;
use DecisionsLint\Diagnostic\LintException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Config\SchemaCatalog
 * @uses \DecisionsLint\Diagnostic\LintException
 */
#[CoversClass(SchemaCatalog::class)]
#[UsesClass(LintException::class)]
final class SchemaCatalogTest extends TestCase
{
    public function testPathPointsIntoTheShippedSchemaDirectory(): void
    {
        self::assertStringEndsWith('/schema/decisions.schema.json', (new SchemaCatalog())->path(SchemaCatalog::DECISIONS));
        self::assertSame('/tmp/x/rules.schema.json', (new SchemaCatalog('/tmp/x'))->path(SchemaCatalog::RULES));
    }

    public function testSchemaDecodesTheShippedSchemas(): void
    {
        $catalog = new SchemaCatalog();
        self::assertSame('object', $catalog->schema(SchemaCatalog::DECISIONS)['type']);
        $definitions = $catalog->schema(SchemaCatalog::RULES)['$defs'];
        self::assertIsArray($definitions);
        self::assertArrayHasKey('ruleFields', $definitions);
    }

    public function testSchemaRejectsAMissingFile(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Schema file not found: /nonexistent/decisions.schema.json. Reinstall decisions-lint');
        (new SchemaCatalog('/nonexistent'))->schema(SchemaCatalog::DECISIONS);
    }

    public function testSchemaRejectsMalformedJson(): void
    {
        $directory = sys_get_temp_dir() . '/decisions-schema-' . uniqid();
        mkdir($directory);
        file_put_contents($directory . '/broken.schema.json', '{');
        file_put_contents($directory . '/scalar.schema.json', '"x"');
        try {
            $catalog = new SchemaCatalog($directory);
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('is not valid JSON');
            $catalog->schema('broken');
        } finally {
            unlink($directory . '/broken.schema.json');
            unlink($directory . '/scalar.schema.json');
            rmdir($directory);
        }
    }

    public function testSchemaRejectsANonObjectDocument(): void
    {
        $directory = sys_get_temp_dir() . '/decisions-schema-' . uniqid();
        mkdir($directory);
        file_put_contents($directory . '/scalar.schema.json', '"x"');
        try {
            $catalog = new SchemaCatalog($directory);
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('must contain a JSON object');
            $catalog->schema('scalar');
        } finally {
            unlink($directory . '/scalar.schema.json');
            rmdir($directory);
        }
    }
}
