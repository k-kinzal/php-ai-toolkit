<?php

declare(strict_types=1);

namespace Tests\Unit\Provider;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\Transport;
use DecisionsLint\Provider\Jev\JevClient;
use DecisionsLint\Provider\Jev\JevDecision;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\OpenAi\OpenAiClient;
use DecisionsLint\Provider\OpenAi\OpenAiDecision;
use DecisionsLint\Provider\ProviderFactory;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\ProviderFactory
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Provider\Jev\JevClient
 * @uses \DecisionsLint\Provider\Jev\JevDecision
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\OpenAi\OpenAiClient
 * @uses \DecisionsLint\Provider\OpenAi\OpenAiDecision
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(ProviderFactory::class)]
#[UsesClass(LintException::class)]
#[UsesClass(JevClient::class)]
#[UsesClass(JevDecision::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(OpenAiClient::class)]
#[UsesClass(OpenAiDecision::class)]
#[UsesClass(ProviderSettings::class)]
final class ProviderFactoryTest extends TestCase
{
    public function testCreateBuildsTheAdapterOfEachProvider(): void
    {
        $transport = new class () implements Transport {
            public function send(array $requests): array
            {
                return [];
            }
        };
        $before = getenv('DECISIONS_TEST_KEY');
        try {
            putenv('DECISIONS_TEST_KEY= secret ');
            $factory = new ProviderFactory();
            self::assertInstanceOf(JevDecision::class, $factory->create(new ProviderSettings('jev', 'jev-latest', 'DECISIONS_TEST_KEY', 'https://a.test'), $transport));
            self::assertInstanceOf(OpenAiDecision::class, $factory->create(new ProviderSettings('openai', 'gpt-6-luna', 'DECISIONS_TEST_KEY', 'https://a.test'), $transport));
        } finally {
            putenv($before === false ? 'DECISIONS_TEST_KEY' : 'DECISIONS_TEST_KEY=' . $before);
        }
    }

    public function testCreateRejectsAnUnknownProvider(): void
    {
        $transport = new class () implements Transport {
            public function send(array $requests): array
            {
                return [];
            }
        };
        $before = getenv('DECISIONS_TEST_KEY');
        try {
            putenv('DECISIONS_TEST_KEY=secret');
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('Unknown provider "other". Set provider.name in decisions.yaml to jev or openai.');
            (new ProviderFactory())->create(new ProviderSettings('other', 'm', 'DECISIONS_TEST_KEY', 'https://a.test'), $transport);
        } finally {
            putenv($before === false ? 'DECISIONS_TEST_KEY' : 'DECISIONS_TEST_KEY=' . $before);
        }
    }

    public function testApiKeyRequiresTheEnvironmentVariable(): void
    {
        $before = getenv('DECISIONS_TEST_KEY');
        try {
            putenv('DECISIONS_TEST_KEY');
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('The DECISIONS_TEST_KEY environment variable is not set. Export the jev API key in it, or set provider.api_key_env');
            (new ProviderFactory())->apiKey(new ProviderSettings('jev', 'jev-latest', 'DECISIONS_TEST_KEY', 'https://a.test'));
        } finally {
            putenv($before === false ? 'DECISIONS_TEST_KEY' : 'DECISIONS_TEST_KEY=' . $before);
        }
    }
}
