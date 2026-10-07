<?php

declare(strict_types=1);

namespace Tests\Unit\Init;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\PresetCatalog
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Init\PresetCatalog::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class PresetCatalogTest extends TestCase
{
    public function testNamesIncludeGenericAndToolPresets(): void
    {
        $names = (new \Guard\Init\PresetCatalog())->names();
        self::assertContains('metrics', $names);
        self::assertContains('disable-doc', $names);
        self::assertContains('phpstan-guard-rules', $names);
        self::assertContains('phpunit9', $names);
        self::assertContains('phpunit13', $names);
        self::assertContains('docgen', $names);
    }

    public function testDefaultsNamesThePhpUnitThirteenFile(): void
    {
        self::assertSame('phpunit.xml.dist', (new \Guard\Init\PresetCatalog())->defaults()['phpunit13']);
    }

    public function testCandidatesPrefersTheVersionedPhpUnitFile(): void
    {
        $candidates = (new \Guard\Init\PresetCatalog())->candidates('phpunit10');
        self::assertSame('phpunit10.xml.dist', $candidates[0]);
    }

    public function testPhpunitCandidatesLeavesUnrelatedPresetsEmpty(): void
    {
        self::assertSame([], (new \Guard\Init\PresetCatalog())->phpunitCandidates('composer'));
    }

    public function testImportPathUsesRulesInsideThePackage(): void
    {
        $root = dirname(__DIR__, 3);
        self::assertSame('rules/metrics.yaml', (new \Guard\Init\PresetCatalog())->importPath($root, 'metrics'));
    }

    public function testImportPathUsesTheInstalledVendorCopy(): void
    {
        $root = sys_get_temp_dir() . '/guard-vendor-preset-' . uniqid();
        mkdir($root . '/vendor/k-kinzal/guard-php/rules', 0777, true);
        file_put_contents($root . '/vendor/k-kinzal/guard-php/rules/composer.yaml', "configuration: []\n");
        self::assertSame('vendor/k-kinzal/guard-php/rules/composer.yaml', (new \Guard\Init\PresetCatalog())->importPath($root, 'composer'));
    }

    public function testLimitsReadTheShippedStandardProfile(): void
    {
        $limits = (new \Guard\Init\PresetCatalog())->limits();
        self::assertSame(['lines' => 500, 'ncloc' => 350], $limits['file']);
        self::assertSame(['lines' => 50, 'cyclomatic_complexity' => 20], $limits['method']);
    }

    public function testAssertKnownRejectsAnUnknownPreset(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Unknown guard import "nope"');
        (new \Guard\Init\PresetCatalog())->assertKnown('nope');
    }

    public function testDirectoryPointsAtTheShippedRules(): void
    {
        self::assertStringEndsWith('/rules', (new \Guard\Init\PresetCatalog())->directory());
    }

    public function testFilePointsAtOnePreset(): void
    {
        self::assertStringEndsWith('/rules/metrics.yaml', (new \Guard\Init\PresetCatalog())->file('metrics'));
    }

    public function testImportsReturnsOneRelativePathPerName(): void
    {
        $paths = (new \Guard\Init\PresetCatalog())->imports(dirname(__DIR__, 3), ['metrics', 'composer']);
        self::assertSame(['rules/metrics.yaml', 'rules/composer.yaml'], $paths);
    }

    public function testRelativePathStaysInsideThePackage(): void
    {
        $catalog = new \Guard\Init\PresetCatalog();
        self::assertSame('rules/metrics.yaml', $catalog->relative(dirname($catalog->directory()), $catalog->file('metrics')));
    }

    public function testAbsoluteReturnsTheGivenPathWhenItDoesNotExist(): void
    {
        $missing = sys_get_temp_dir() . '/guard-missing-' . uniqid();
        self::assertSame($missing, (new \Guard\Init\PresetCatalog())->absolute($missing));
    }

    public function testPartsSplitsDirectories(): void
    {
        self::assertSame(['rules', 'metrics.yaml'], (new \Guard\Init\PresetCatalog())->parts('rules/metrics.yaml'));
    }

    public function testReadLoadsTheComposerPreset(): void
    {
        $document = (new \Guard\Init\PresetCatalog())->read('composer');
        self::assertArrayHasKey('configuration', $document);
    }

    public function testRuleIdsListsPhpStanLevel(): void
    {
        self::assertSame(['phpstan.level', 'phpstan.strict-rules', 'phpstan.rules', 'phpstan.extension'], (new \Guard\Init\PresetCatalog())->ruleIds('phpstan'));
    }

    public function testMetricsRejectsANonIntegerLimit(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('must be an integer');
        (new \Guard\Init\PresetCatalog())->metrics(['file' => ['lines' => '500']]);
    }

    public function testIsMappingAcceptsADocument(): void
    {
        self::assertTrue((new \Guard\Init\PresetCatalog())->isMapping(['metrics' => []]));
        self::assertFalse((new \Guard\Init\PresetCatalog())->isMapping(['metrics']));
    }
}
