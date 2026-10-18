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
    }
}
