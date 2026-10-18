<?php

declare(strict_types=1);

namespace Tests\Unit\Provider;

use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(ProviderSettings::class)]
final class ProviderSettingsTest extends TestCase
{
    public function testDefaultsDescribeEachProvider(): void
    {
        $jev = ProviderSettings::defaults(ProviderSettings::JEV);
        self::assertSame('jev', $jev->name);
        self::assertSame('jev-latest', $jev->model);
        self::assertSame('TYPESAFE_API_KEY', $jev->apiKeyEnv);
        self::assertSame('https://api.typesafe.ai', $jev->baseUrl);
        self::assertSame(60, $jev->timeout);
        self::assertSame(8, $jev->concurrency);
        $openai = ProviderSettings::defaults(ProviderSettings::OPENAI);
        self::assertSame('gpt-6-luna', $openai->model);
        self::assertSame('OPENAI_API_KEY', $openai->apiKeyEnv);
        self::assertSame('https://api.openai.com', $openai->baseUrl);
        $ollama = ProviderSettings::defaults(ProviderSettings::OLLAMA);
        self::assertSame('http://localhost:11434', $ollama->baseUrl);
        self::assertSame(300, $ollama->timeout);
        self::assertSame(2, $ollama->concurrency);
        self::assertSame('http://localhost:8080', ProviderSettings::defaults(ProviderSettings::LOCALAI)->baseUrl);
        self::assertSame('http://localhost:8000', ProviderSettings::defaults(ProviderSettings::OPENAI_COMPATIBLE)->baseUrl);
        self::assertSame(['jev', 'openai', 'ollama', 'localai', 'openai-compatible'], ProviderSettings::NAMES);
    }

    public function testRequiresApiKeyOnlyForHostedDecisionApis(): void
    {
        self::assertTrue(ProviderSettings::defaults('jev')->requiresApiKey());
        self::assertTrue(ProviderSettings::defaults('openai')->requiresApiKey());
        self::assertFalse(ProviderSettings::defaults('ollama')->requiresApiKey());
        self::assertFalse(ProviderSettings::defaults('localai')->requiresApiKey());
        self::assertFalse(ProviderSettings::defaults('openai-compatible')->requiresApiKey());
    }
}
