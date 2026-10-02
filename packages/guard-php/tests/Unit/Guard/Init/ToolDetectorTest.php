<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Init;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Init\ToolDetector
 */
#[CoversClass(\Toolkit\Guard\Init\ToolDetector::class)]
final class ToolDetectorTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testPackagesPrefersTheInstalledVersionOverConstraintAlternatives(): void
    {
        $root = sys_get_temp_dir() . '/guard-tools-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/composer.json', '{"require-dev":{"phpunit/phpunit":"^9.6 || ^13"}}');
        file_put_contents($root . '/composer.lock', '{"packages-dev":[{"name":"phpunit/phpunit","version":"13.0.0"}]}');
        self::assertSame('13.0.0', (new \Toolkit\Guard\Init\ToolDetector())->packages($root)['phpunit/phpunit']);
    }

    /**
     * @throws JsonException
     */
    public function testManifestHandlesAbsentComposerFiles(): void
    {
        self::assertSame([], (new \Toolkit\Guard\Init\ToolDetector())->manifest(sys_get_temp_dir(), 'absent-' . uniqid() . '.json'));
    }
    /**
     * @throws JsonException
     */
    public function testSourcesUsesAutoloadDirectories(): void
    {
        $root = sys_get_temp_dir() . '/guard-sources-' . uniqid();
        mkdir($root);
        mkdir($root . '/lib');
        file_put_contents($root . '/composer.json', '{"autoload":{"psr-4":{"App\\\\":"lib/"}}}');
        self::assertSame(['lib'], (new \Toolkit\Guard\Init\ToolDetector())->sources($root));
    }
}
