<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Init;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Init\PresetSelector
 * @uses \Toolkit\Guard\Init\PresetCatalog
 * @uses \Toolkit\Guard\Init\Recommendations
 * @uses \Toolkit\Guard\Init\PresetOverrides
 * @uses \Toolkit\Guard\Init\ToolDetector
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Init\PresetSelector::class)]
#[UsesClass(\Toolkit\Guard\Init\PresetCatalog::class)]
#[UsesClass(\Toolkit\Guard\Init\PresetOverrides::class)]
#[UsesClass(\Toolkit\Guard\Init\Recommendations::class)]
#[UsesClass(\Toolkit\Guard\Init\ToolDetector::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class PresetSelectorTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testDetectMatchesInstalledTools(): void
    {
        $root = sys_get_temp_dir() . '/guard-detect-' . uniqid();
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/composer.json', '{"require-dev":{"phpunit/phpunit":"9.6.0","k-kinzal/phpstan-guard-rules":"^1.0"}}');
        file_put_contents($root . '/phpstan.neon', "parameters:\n    level: max\n");
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        $names = (new \Toolkit\Guard\Init\PresetSelector())->detect($root);
        self::assertSame(['quality', 'structure', 'phpstan', 'phpstan-guard-rules', 'phpunit9', 'composer'], $names);
    }

    /**
     * @throws JsonException
     */
    public function testSelectDropsPresetsReplacedByLegacyFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-select-legacy-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/loc.yaml', "scan: {roots: [lib]}\n");
        file_put_contents($root . '/tree.yaml', "paths: [src]\n");
        $names = (new \Toolkit\Guard\Init\PresetSelector())->select($root, ['quality', 'structure', 'composer', 'quality']);
        self::assertSame(['composer'], $names);
    }

    /**
     * @throws JsonException
     */
    public function testSelectRejectsAnUnknownExplicitName(): void
    {
        $root = sys_get_temp_dir() . '/guard-select-unknown-' . uniqid();
        mkdir($root);
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Unknown guard import "nope"');
        (new \Toolkit\Guard\Init\PresetSelector())->select($root, ['nope']);
    }

    /**
     * @throws JsonException
     */
    public function testPhpstanPresetsSkipsProjectsWithoutNeon(): void
    {
        $root = sys_get_temp_dir() . '/guard-no-neon-' . uniqid();
        mkdir($root);
        self::assertSame([], (new \Toolkit\Guard\Init\PresetSelector())->phpstanPresets($root));
    }

    /**
     * @throws JsonException
     */
    /**
     * @throws JsonException
     */
    public function testPhpunitPresetsUsesTheResolvedMajorForAnUnversionedFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-phpunit-modern-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/composer.json', '{"require-dev":{"phpunit/phpunit":"^10.5"}}');
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        $selector = new \Toolkit\Guard\Init\PresetSelector();
        self::assertSame(['phpunit10'], $selector->phpunitPresets($root));
        self::assertSame([], $selector->phpunitPresets($root . '-missing'));
    }

    public function testPhpunitMajorIgnoresAMultiMajorConstraint(): void
    {
        $selector = new \Toolkit\Guard\Init\PresetSelector();
        self::assertSame('10', $selector->phpunitMajor('10.5.0'));
        self::assertSame('13', $selector->phpunitMajor('^13.0'));
        self::assertNull($selector->phpunitMajor('^9.6 || ^10.5 || ^11'));
    }

    public function testDoctestPresetsFollowsASuiteOrRequiredExamples(): void
    {
        $root = sys_get_temp_dir() . '/guard-doctest-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit10.xml.dist', '<phpunit><file>src/Doctest/DoctestSuite.php</file></phpunit>');
        $selector = new \Toolkit\Guard\Init\PresetSelector();
        self::assertSame(['doctest10'], $selector->doctestPresets($root, ['phpunit10']));
    }

    public function testRequiresExamplesReadsThePhpStanFlag(): void
    {
        $root = sys_get_temp_dir() . '/guard-examples-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpstan.neon', "parameters:\n    toolkit:\n        allRules: true\n");
        self::assertTrue((new \Toolkit\Guard\Init\PresetSelector())->requiresExamples($root));
    }

    /**
     * @throws JsonException
     */
    public function testToolPresetsMatchesInstalledFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-tools-' . uniqid();
        mkdir($root . '/.github/workflows', 0777, true);
        file_put_contents($root . '/composer.json', '{"require-dev":{"deptrac/deptrac":"^2.0","giorgiosironi/eris":"^1.0"}}');
        file_put_contents($root . '/deptrac.yaml', "deptrac: {}\n");
        file_put_contents($root . '/infection.json5', "{}\n");
        file_put_contents($root . '/.github/workflows/ci.yml', "name: CI\n");
        $names = (new \Toolkit\Guard\Init\PresetSelector())->toolPresets($root);
        self::assertSame(['deptrac', 'infection', 'github-actions', 'pbt'], $names);
    }

    /**
     * @throws JsonException
     */
    public function testPackageNameReadsComposer(): void
    {
        $root = sys_get_temp_dir() . '/guard-package-name-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/composer.json', '{"name":"k-kinzal/docgen-php"}');
        self::assertSame('k-kinzal/docgen-php', (new \Toolkit\Guard\Init\PresetSelector())->packageName($root));
    }

    public function testUniqueDropsRepeatedNames(): void
    {
        self::assertSame(['quality', 'composer'], (new \Toolkit\Guard\Init\PresetSelector())->unique(['quality', 'composer', 'quality']));
    }

    public function testWithoutRemovesOnePreset(): void
    {
        self::assertSame(['structure'], (new \Toolkit\Guard\Init\PresetSelector())->without(['quality', 'structure'], 'quality'));
    }
}
