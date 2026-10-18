<?php

declare(strict_types=1);

namespace Tests\Unit\Provider;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\Fake\FakeTransport;
use DecisionsLint\Provider\Jev\JevClient;
use DecisionsLint\Provider\Jev\JevDecision;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\Ollama\OllamaClient;
use DecisionsLint\Provider\Ollama\OllamaDecision;
use DecisionsLint\Provider\OpenAi\OpenAiClient;
use DecisionsLint\Provider\OpenAi\OpenAiDecision;
use DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsClient;
use DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsDecision;
use DecisionsLint\Provider\ProviderFactory;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\ProviderFactory
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Provider\Fake\FakeTransport
 * @uses \DecisionsLint\Provider\Jev\JevClient
 * @uses \DecisionsLint\Provider\Jev\JevDecision
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\Ollama\OllamaClient
 * @uses \DecisionsLint\Provider\Ollama\OllamaDecision
 * @uses \DecisionsLint\Provider\OpenAi\OpenAiClient
 * @uses \DecisionsLint\Provider\OpenAi\OpenAiDecision
 * @uses \DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsClient
 * @uses \DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsDecision
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(ProviderFactory::class)]
#[UsesClass(LintException::class)]
#[UsesClass(FakeTransport::class)]
#[UsesClass(JevClient::class)]
#[UsesClass(JevDecision::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(OllamaClient::class)]
#[UsesClass(OllamaDecision::class)]
#[UsesClass(OpenAiClient::class)]
#[UsesClass(OpenAiDecision::class)]
#[UsesClass(ChatCompletionsClient::class)]
#[UsesClass(ChatCompletionsDecision::class)]
#[UsesClass(ProviderSettings::class)]
final class ProviderFactoryTest extends TestCase
{
    public function testCreateBuildsTheAdapterOfEachProvider(): void
    {
        $transport = new FakeTransport();
        $before = getenv('DECISIONS_TEST_KEY');
        try {
            putenv('DECISIONS_TEST_KEY= secret ');
            $factory = new ProviderFactory();
            self::assertInstanceOf(JevDecision::class, $factory->create(new ProviderSettings('jev', 'jev-latest', 'DECISIONS_TEST_KEY', 'https://a.test'), $transport));
            self::assertInstanceOf(OpenAiDecision::class, $factory->create(new ProviderSettings('openai', 'gpt-6-luna', 'DECISIONS_TEST_KEY', 'https://a.test'), $transport));
            self::assertInstanceOf(OllamaDecision::class, $factory->create(new ProviderSettings('ollama', 'llama3.1', 'DECISIONS_TEST_KEY', 'http://localhost:11434'), $transport));
            self::assertInstanceOf(ChatCompletionsDecision::class, $factory->create(new ProviderSettings('localai', 'gpt-4', 'DECISIONS_TEST_KEY', 'http://localhost:8080'), $transport));
            self::assertInstanceOf(ChatCompletionsDecision::class, $factory->create(new ProviderSettings('openai-compatible', 'm', 'DECISIONS_TEST_KEY', 'http://localhost:8000'), $transport));
        } finally {
            putenv($before === false ? 'DECISIONS_TEST_KEY' : 'DECISIONS_TEST_KEY=' . $before);
        }
    }

    public function testCreateRejectsAnUnknownProvider(): void
    {
        $before = getenv('DECISIONS_TEST_KEY');
        try {
            putenv('DECISIONS_TEST_KEY=secret');
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('Unknown provider "other". Set provider.name in decisions.yaml to one of: jev, openai, ollama, localai, openai-compatible.');
            (new ProviderFactory())->create(new ProviderSettings('other', 'm', 'DECISIONS_TEST_KEY', 'https://a.test'), new FakeTransport());
        } finally {
            putenv($before === false ? 'DECISIONS_TEST_KEY' : 'DECISIONS_TEST_KEY=' . $before);
        }
    }

    public function testApiKeyRequiresTheEnvironmentVariableForHostedProvidersOnly(): void
    {
        $before = getenv('DECISIONS_TEST_KEY');
        try {
            putenv('DECISIONS_TEST_KEY');
            $factory = new ProviderFactory();
            self::assertSame('', $factory->apiKey(new ProviderSettings('ollama', 'llama3.1', 'DECISIONS_TEST_KEY', 'http://localhost:11434')));
            putenv('DECISIONS_TEST_KEY= local ');
            self::assertSame('local', $factory->apiKey(new ProviderSettings('localai', 'gpt-4', 'DECISIONS_TEST_KEY', 'http://localhost:8080')));
            putenv('DECISIONS_TEST_KEY');
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('The DECISIONS_TEST_KEY environment variable is not set. Export the jev API key in it, or set provider.api_key_env');
            $factory->apiKey(new ProviderSettings('jev', 'jev-latest', 'DECISIONS_TEST_KEY', 'https://a.test'));
        } finally {
            putenv($before === false ? 'DECISIONS_TEST_KEY' : 'DECISIONS_TEST_KEY=' . $before);
        }
    }
}
