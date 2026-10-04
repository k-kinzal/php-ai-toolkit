<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Init;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Init\PresetOverrides
 * @uses \Toolkit\Guard\Init\Initializer
 * @uses \Toolkit\Guard\Init\PresetCatalog
 * @uses \Toolkit\Guard\Init\Recommendations
 * @uses \Toolkit\Guard\Init\ToolDetector
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Init\PresetOverrides::class)]
#[UsesClass(\Toolkit\Guard\Init\Initializer::class)]
#[UsesClass(\Toolkit\Guard\Init\PresetCatalog::class)]
#[UsesClass(\Toolkit\Guard\Init\Recommendations::class)]
#[UsesClass(\Toolkit\Guard\Init\ToolDetector::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class PresetOverridesTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testApplyPointsPhpUnitRulesAtTheDetectedFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-phpunit-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit.xml', '<phpunit/>');
        $document = (new \Toolkit\Guard\Init\PresetOverrides())->apply($root, ['phpunit10'], ['version' => 1]);
        $encoded = json_encode($document['configuration'], JSON_THROW_ON_ERROR);
        self::assertStringContainsString('"file":"phpunit.xml"', $encoded);
        self::assertStringContainsString('phpunit10.beStrictAboutChangesToGlobalState', $encoded);
        self::assertStringNotContainsString('assert', $encoded);
    }

    /**
     * @throws JsonException
     */
    public function testDirectoriesAddsRulesForOtherSourceRoots(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-lib-' . uniqid();
        mkdir($root . '/lib', 0777, true);
        file_put_contents($root . '/composer.json', '{"autoload":{"psr-4":{"Lib\\\\":"lib/"}}}');
        $rules = (new \Toolkit\Guard\Init\PresetOverrides())->directories($root, ['structure']);
        self::assertSame('lib', $rules[0]['path']);
        self::assertSame('lib/**', $rules[1]['path']);
    }

    /**
     * @throws JsonException
     */
    public function testApplySkipsStructureWhenThatPresetIsAbsent(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-skip-' . uniqid();
        mkdir($root . '/lib', 0777, true);
        file_put_contents($root . '/composer.json', '{"autoload":{"psr-4":{"Lib\\\\":"lib/"}}}');
        $document = (new \Toolkit\Guard\Init\PresetOverrides())->apply($root, ['composer'], ['version' => 1]);
        self::assertArrayNotHasKey('structure', $document);
        self::assertArrayNotHasKey('configuration', $document);
    }

    public function testFilesOverridesThePhpStanDistPath(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-neon-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpstan.neon.dist', "parameters:\n    level: 5\n");
        $rules = (new \Toolkit\Guard\Init\PresetOverrides())->files($root, ['phpstan', 'phpstan-guard-rules']);
        self::assertSame('phpstan.level', $rules[0]['id']);
        self::assertSame('phpstan.neon.dist', $rules[0]['file']);
        self::assertSame('phpstan.all-rules', $rules[4]['id']);
    }

    public function testActualUsesThePlainPhpUnitFileWhenTheVersionedOneIsMissing(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-actual-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        self::assertSame('phpunit.xml.dist', (new \Toolkit\Guard\Init\PresetOverrides())->actual($root, 'phpunit10'));
    }

    public function testPbtExcludesOnlyExistingPhpUnitFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-pbt-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        $rules = (new \Toolkit\Guard\Init\PresetOverrides())->pbtExcludes($root, ['pbt']);
        self::assertSame('phpunit.xml.dist', $rules[0]['file']);
        self::assertSame([], (new \Toolkit\Guard\Init\PresetOverrides())->pbtExcludes($root, ['composer']));
    }

    public function testRefileSkipsPresetsThatWereNotSelected(): void
    {
        self::assertSame([], (new \Toolkit\Guard\Init\PresetOverrides())->refile(['composer'], ['phpstan'], 'phpstan.neon.dist'));
    }
}
