<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use DecisionsLint\Config\ProviderReader;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Config\ProviderReader
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(ProviderReader::class)]
#[UsesClass(ProviderSettings::class)]
final class ProviderReaderTest extends TestCase
{
    public function testReadFillsTheDefaultsOfTheNamedProvider(): void
    {
        $settings = (new ProviderReader())->read(['name' => 'openai', 'timeout' => 10]);
        self::assertSame('openai', $settings->name);
        self::assertSame('gpt-6-luna', $settings->model);
        self::assertSame('OPENAI_API_KEY', $settings->apiKeyEnv);
        self::assertSame(10, $settings->timeout);
        self::assertSame(8, $settings->concurrency);
    }

    public function testReadDefaultsToJevAndTrimsTheBaseUrl(): void
    {
        $settings = (new ProviderReader())->read(['model' => 'jev-1.14.0', 'api_key_env' => 'MY_KEY', 'base_url' => 'https://proxy.test/', 'concurrency' => 2]);
        self::assertSame('jev', $settings->name);
        self::assertSame('jev-1.14.0', $settings->model);
        self::assertSame('MY_KEY', $settings->apiKeyEnv);
        self::assertSame('https://proxy.test', $settings->baseUrl);
        self::assertSame(2, $settings->concurrency);
        self::assertSame('jev-latest', (new ProviderReader())->read([])->model);
    }
}
