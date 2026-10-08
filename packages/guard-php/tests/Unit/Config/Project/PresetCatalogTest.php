<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\PresetCatalog
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
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
#[CoversClass(\Guard\Config\Project\PresetCatalog::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
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
final class PresetCatalogTest extends TestCase
{
    public function testNamesIncludeGenericAndToolPresets(): void
    {
        $names = (new \Guard\Config\Project\PresetCatalog())->names();
        self::assertContains('metrics', $names);
        self::assertContains('disable-doc', $names);
        self::assertContains('phpstan-guard-rules', $names);
        self::assertContains('phpunit9', $names);
        self::assertContains('phpunit13', $names);
        self::assertContains('docgen', $names);
        self::assertContains('readme-md', $names);
    }

    public function testDocumentsMapsMarkdownPresetsToTheirFiles(): void
    {
        self::assertSame(['agents-md' => 'AGENTS.md', 'claude-md' => 'CLAUDE.md', 'readme-md' => 'README.md'], (new \Guard\Config\Project\PresetCatalog())->documents());
    }

    /**
     * @dataProvider providerMarkdownPresets
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerMarkdownPresets')]
    public function testMarkdownPresetDeclaresOnlyItsDocument(string $name, string $document): void
    {
        $documentation = (new \Guard\Config\Project\PresetCatalog())->read($name)['documentation'] ?? null;

        self::assertIsArray($documentation);
        self::assertIsArray($documentation['files'] ?? null);
        self::assertSame([$document], array_keys($documentation['files']));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function providerMarkdownPresets(): iterable
    {
        yield 'agents-md' => ['agents-md', 'AGENTS.md'];
        yield 'claude-md' => ['claude-md', 'CLAUDE.md'];
        yield 'readme-md' => ['readme-md', 'README.md'];
    }

    public function testDefaultsNamesThePhpUnitThirteenFile(): void
    {
        self::assertSame('phpunit.xml.dist', (new \Guard\Config\Project\PresetCatalog())->defaults()['phpunit13']);
    }

    public function testCandidatesPrefersTheVersionedPhpUnitFile(): void
    {
        $candidates = (new \Guard\Config\Project\PresetCatalog())->candidates('phpunit10');
        self::assertSame('phpunit10.xml.dist', $candidates[0]);
    }

    public function testPhpunitCandidatesLeavesUnrelatedPresetsEmpty(): void
    {
        self::assertSame([], (new \Guard\Config\Project\PresetCatalog())->phpunitCandidates('composer'));
    }

    public function testImportPathUsesRulesInsideThePackage(): void
    {
        $root = dirname(__DIR__, 4);
        self::assertSame('rules/metrics.yaml', (new \Guard\Config\Project\PresetCatalog())->importPath($root, 'metrics'));
    }

    public function testImportPathUsesTheInstalledVendorCopy(): void
    {
        $root = sys_get_temp_dir() . '/guard-vendor-preset-' . uniqid();
        mkdir($root . '/vendor/k-kinzal/guard-php/rules', 0777, true);
        file_put_contents($root . '/vendor/k-kinzal/guard-php/rules/composer.yaml', "configuration: []\n");
        self::assertSame('vendor/k-kinzal/guard-php/rules/composer.yaml', (new \Guard\Config\Project\PresetCatalog())->importPath($root, 'composer'));
    }

    public function testLimitsReadTheShippedStandardProfile(): void
    {
        $limits = (new \Guard\Config\Project\PresetCatalog())->limits();
        self::assertSame(['lines' => 500, 'ncloc' => 350], $limits['file']);
        self::assertSame(['lines' => 50, 'cyclomatic_complexity' => 20], $limits['method']);
    }

    public function testAssertKnownRejectsAnUnknownPreset(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('Unknown guard import "nope"');
        (new \Guard\Config\Project\PresetCatalog())->assertKnown('nope');
    }

    public function testDirectoryPointsAtTheShippedRules(): void
    {
        self::assertStringEndsWith('/rules', (new \Guard\Config\Project\PresetCatalog())->directory());
    }

    public function testFilePointsAtOnePreset(): void
    {
        self::assertStringEndsWith('/rules/metrics.yaml', (new \Guard\Config\Project\PresetCatalog())->file('metrics'));
    }

    public function testImportsReturnsOneRelativePathPerName(): void
    {
        $paths = (new \Guard\Config\Project\PresetCatalog())->imports(dirname(__DIR__, 4), ['metrics', 'composer']);
        self::assertSame(['rules/metrics.yaml', 'rules/composer.yaml'], $paths);
    }

    public function testRelativePathStaysInsideThePackage(): void
    {
        $catalog = new \Guard\Config\Project\PresetCatalog();
        self::assertSame('rules/metrics.yaml', $catalog->relative(dirname($catalog->directory()), $catalog->file('metrics')));
    }

    public function testAbsoluteReturnsTheGivenPathWhenItDoesNotExist(): void
    {
        $missing = sys_get_temp_dir() . '/guard-missing-' . uniqid();
        self::assertSame($missing, (new \Guard\Config\Project\PresetCatalog())->absolute($missing));
    }

    public function testPartsSplitsDirectories(): void
    {
        self::assertSame(['rules', 'metrics.yaml'], (new \Guard\Config\Project\PresetCatalog())->parts('rules/metrics.yaml'));
    }

    public function testReadLoadsTheComposerPreset(): void
    {
        $document = (new \Guard\Config\Project\PresetCatalog())->read('composer');
        self::assertArrayHasKey('configuration', $document);
    }

    public function testRuleIdsListsPhpStanLevel(): void
    {
        self::assertSame(['phpstan.level', 'phpstan.strict-rules', 'phpstan.rules', 'phpstan.extension'], (new \Guard\Config\Project\PresetCatalog())->ruleIds('phpstan'));
    }

    public function testMetricsRejectsANonIntegerLimit(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('must be an integer');
        (new \Guard\Config\Project\PresetCatalog())->metrics(['file' => ['lines' => '500']]);
    }

    public function testIsMappingAcceptsADocument(): void
    {
        self::assertTrue((new \Guard\Config\Project\PresetCatalog())->isMapping(['metrics' => []]));
        self::assertFalse((new \Guard\Config\Project\PresetCatalog())->isMapping(['metrics']));
    }
}
