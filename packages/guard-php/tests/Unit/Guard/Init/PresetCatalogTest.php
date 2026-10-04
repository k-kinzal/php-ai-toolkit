<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Init;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Init\PresetCatalog
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Init\PresetCatalog::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class PresetCatalogTest extends TestCase
{
    public function testNamesIncludeGenericAndToolPresets(): void
    {
        $names = (new \Toolkit\Guard\Init\PresetCatalog())->names();
        self::assertContains('quality', $names);
        self::assertContains('phpstan-guard-rules', $names);
        self::assertContains('phpunit9', $names);
        self::assertContains('phpunit13', $names);
        self::assertContains('docgen', $names);
    }

    public function testDefaultsNamesThePhpUnitThirteenFile(): void
    {
        self::assertSame('phpunit.xml.dist', (new \Toolkit\Guard\Init\PresetCatalog())->defaults()['phpunit13']);
    }

    public function testCandidatesPrefersTheVersionedPhpUnitFile(): void
    {
        $candidates = (new \Toolkit\Guard\Init\PresetCatalog())->candidates('phpunit10');
        self::assertSame('phpunit10.xml.dist', $candidates[0]);
    }

    public function testPhpunitCandidatesLeavesUnrelatedPresetsEmpty(): void
    {
        self::assertSame([], (new \Toolkit\Guard\Init\PresetCatalog())->phpunitCandidates('composer'));
    }

    public function testImportPathUsesRulesInsideThePackage(): void
    {
        $root = dirname(__DIR__, 4);
        self::assertSame('rules/quality.yaml', (new \Toolkit\Guard\Init\PresetCatalog())->importPath($root, 'quality'));
    }

    public function testImportPathUsesTheInstalledVendorCopy(): void
    {
        $root = sys_get_temp_dir() . '/guard-vendor-preset-' . uniqid();
        mkdir($root . '/vendor/k-kinzal/guard-php/rules', 0777, true);
        file_put_contents($root . '/vendor/k-kinzal/guard-php/rules/composer.yaml', "configuration: []\n");
        self::assertSame('vendor/k-kinzal/guard-php/rules/composer.yaml', (new \Toolkit\Guard\Init\PresetCatalog())->importPath($root, 'composer'));
    }

    public function testLimitsReadTheShippedStandardProfile(): void
    {
        $limits = (new \Toolkit\Guard\Init\PresetCatalog())->limits();
        self::assertSame(['lines' => 500, 'ncloc' => 350], $limits['file']);
        self::assertSame(['lines' => 50, 'cyclomatic_complexity' => 20], $limits['method']);
    }

    public function testAssertKnownRejectsAnUnknownPreset(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Unknown guard import "nope"');
        (new \Toolkit\Guard\Init\PresetCatalog())->assertKnown('nope');
    }

    public function testDirectoryPointsAtTheShippedRules(): void
    {
        self::assertStringEndsWith('/rules', (new \Toolkit\Guard\Init\PresetCatalog())->directory());
    }

    public function testFilePointsAtOnePreset(): void
    {
        self::assertStringEndsWith('/rules/quality.yaml', (new \Toolkit\Guard\Init\PresetCatalog())->file('quality'));
    }

    public function testImportsReturnsOneRelativePathPerName(): void
    {
        $paths = (new \Toolkit\Guard\Init\PresetCatalog())->imports(dirname(__DIR__, 4), ['quality', 'composer']);
        self::assertSame(['rules/quality.yaml', 'rules/composer.yaml'], $paths);
    }

    public function testRelativePathStaysInsideThePackage(): void
    {
        $catalog = new \Toolkit\Guard\Init\PresetCatalog();
        self::assertSame('rules/quality.yaml', $catalog->relative(dirname($catalog->directory()), $catalog->file('quality')));
    }

    public function testAbsoluteReturnsTheGivenPathWhenItDoesNotExist(): void
    {
        $missing = sys_get_temp_dir() . '/guard-missing-' . uniqid();
        self::assertSame($missing, (new \Toolkit\Guard\Init\PresetCatalog())->absolute($missing));
    }

    public function testPartsSplitsDirectories(): void
    {
        self::assertSame(['rules', 'quality.yaml'], (new \Toolkit\Guard\Init\PresetCatalog())->parts('rules/quality.yaml'));
    }

    public function testReadLoadsTheComposerPreset(): void
    {
        $document = (new \Toolkit\Guard\Init\PresetCatalog())->read('composer');
        self::assertArrayHasKey('configuration', $document);
    }

    public function testRuleIdsListsPhpStanLevel(): void
    {
        self::assertSame(['phpstan.level', 'phpstan.strict-rules', 'phpstan.rules', 'phpstan.extension'], (new \Toolkit\Guard\Init\PresetCatalog())->ruleIds('phpstan'));
    }

    public function testMetricsRejectsANonIntegerLimit(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('must be an integer');
        (new \Toolkit\Guard\Init\PresetCatalog())->metrics(['file' => ['lines' => '500']]);
    }

    public function testIsMappingAcceptsTheQualityDocument(): void
    {
        self::assertTrue((new \Toolkit\Guard\Init\PresetCatalog())->isMapping(['quality' => []]));
        self::assertFalse((new \Toolkit\Guard\Init\PresetCatalog())->isMapping(['quality']));
    }
}
