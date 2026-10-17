<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project;

use Guard\Config\Configuration;
use Guard\Config\Project\PresetCatalog;
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
use PHPUnit\Framework\Attributes\DataProvider;
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
#[CoversClass(PresetCatalog::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
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
final class PresetCatalogTest extends TestCase
{
    public function testNamesIncludeGenericAndToolPresets(): void
    {
        $names = (new PresetCatalog())->names();
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
        self::assertSame(['agents-md' => 'AGENTS.md', 'claude-md' => 'CLAUDE.md', 'readme-md' => 'README.md'], (new PresetCatalog())->documents());
    }

    /**
     * @dataProvider providerMarkdownPresets
     */
    #[DataProvider('providerMarkdownPresets')]
    public function testMarkdownPresetDeclaresOnlyItsDocument(string $name, string $document): void
    {
        $documentation = (new PresetCatalog())->read($name)['documentation'] ?? null;

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
        self::assertSame('phpunit.xml.dist', (new PresetCatalog())->defaults()['phpunit13']);
    }

    public function testCandidatesPrefersTheVersionedPhpUnitFile(): void
    {
        $candidates = (new PresetCatalog())->candidates('phpunit10');
        self::assertSame('phpunit10.xml.dist', $candidates[0]);
    }

    public function testPhpunitCandidatesLeavesUnrelatedPresetsEmpty(): void
    {
        self::assertSame([], (new PresetCatalog())->phpunitCandidates('composer'));
    }

    public function testImportPathUsesRulesInsideThePackage(): void
    {
        $root = dirname(__DIR__, 4);
        self::assertSame('rules/metrics.yaml', (new PresetCatalog())->importPath($root, 'metrics'));
    }

    public function testImportPathUsesTheInstalledVendorCopy(): void
    {
        $root = sys_get_temp_dir() . '/guard-vendor-preset-' . uniqid();
        mkdir($root . '/vendor/k-kinzal/guard-php/rules', 0777, true);
        file_put_contents($root . '/vendor/k-kinzal/guard-php/rules/composer.yaml', "configuration: []\n");
        self::assertSame('vendor/k-kinzal/guard-php/rules/composer.yaml', (new PresetCatalog())->importPath($root, 'composer'));
    }

    public function testLimitsReadTheShippedStandardProfile(): void
    {
        $limits = (new PresetCatalog())->limits();
        self::assertSame(['lines' => 500, 'ncloc' => 350], $limits['file']);
        self::assertSame(['lines' => 50, 'cyclomatic_complexity' => 20], $limits['method']);
    }

    public function testAssertKnownRejectsAnUnknownPreset(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Unknown guard import "nope"');
        (new PresetCatalog())->assertKnown('nope');
    }

    public function testDirectoryPointsAtTheShippedRules(): void
    {
        self::assertStringEndsWith('/rules', (new PresetCatalog())->directory());
    }

    public function testFilePointsAtOnePreset(): void
    {
        self::assertStringEndsWith('/rules/metrics.yaml', (new PresetCatalog())->file('metrics'));
    }

    public function testImportsReturnsOneRelativePathPerName(): void
    {
        $paths = (new PresetCatalog())->imports(dirname(__DIR__, 4), ['metrics', 'composer']);
        self::assertSame(['rules/metrics.yaml', 'rules/composer.yaml'], $paths);
    }

    public function testRelativePathStaysInsideThePackage(): void
    {
        $catalog = new PresetCatalog();
        self::assertSame('rules/metrics.yaml', $catalog->relative(dirname($catalog->directory()), $catalog->file('metrics')));
    }

    public function testAbsoluteReturnsTheGivenPathWhenItDoesNotExist(): void
    {
        $missing = sys_get_temp_dir() . '/guard-missing-' . uniqid();
        self::assertSame($missing, (new PresetCatalog())->absolute($missing));
    }

    public function testPartsSplitsDirectories(): void
    {
        self::assertSame(['rules', 'metrics.yaml'], (new PresetCatalog())->parts('rules/metrics.yaml'));
    }

    public function testReadLoadsTheComposerPreset(): void
    {
        $document = (new PresetCatalog())->read('composer');
        self::assertArrayHasKey('configuration', $document);
    }

    public function testRuleIdsListsPhpStanLevel(): void
    {
        self::assertSame(['phpstan.level', 'phpstan.strict-rules', 'phpstan.rules', 'phpstan.extension'], (new PresetCatalog())->ruleIds('phpstan'));
    }

    public function testMetricsRejectsANonIntegerLimit(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('must be an integer');
        (new PresetCatalog())->metrics(['file' => ['lines' => '500']]);
    }

    public function testIsMappingAcceptsADocument(): void
    {
        self::assertTrue((new PresetCatalog())->isMapping(['metrics' => []]));
        self::assertFalse((new PresetCatalog())->isMapping(['metrics']));
    }
}
