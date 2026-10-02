<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Init;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Init\Recommendations
 * @uses \Toolkit\Guard\Init\ToolDetector
 */
#[CoversClass(\Toolkit\Guard\Init\Recommendations::class)]
#[UsesClass(\Toolkit\Guard\Init\ToolDetector::class)]
final class RecommendationsTest extends TestCase
{
    public function testPhpunitUsesTheInstalledGeneration(): void
    {
        $rules = (new \Toolkit\Guard\Init\Recommendations())->phpunit('phpunit.xml', '13.0.0');
        self::assertSame('/phpunit/@failOnAllIssues', $rules[4]['select']);
        self::assertSame(['equals' => 'true'], $rules[4]['assert']);
    }

    /**
     * @throws JsonException
     */
    public function testRulesSelectsExistingPhpStanConfig(): void
    {
        $root = sys_get_temp_dir() . '/guard-phpstan-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/composer.json', '{"require-dev":{"phpstan/phpstan":"^2.0"}}');
        file_put_contents($root . '/phpstan.neon.dist', "parameters:\n    level: 5\n");
        $rules = (new \Toolkit\Guard\Init\Recommendations())->rules($root);
        self::assertSame('phpstan.neon.dist', $rules[0]['file']);
        self::assertSame(['equals' => 'max'], $rules[0]['assert']);
    }
    public function testExistingDoesNotInventConfigurationFiles(): void
    {
        self::assertNull((new \Toolkit\Guard\Init\Recommendations())->existing(sys_get_temp_dir(), ['nonexistent-' . uniqid() . '.xml']));
    }
    public function testExactDefaultsToRequiredEnforcement(): void
    {
        $rule = (new \Toolkit\Guard\Init\Recommendations())->exact('flag', 'x.json', 'json', '/flag', true);
        self::assertSame('required', $rule['level']);
        self::assertSame(['equals' => true], $rule['assert']);
    }
}
