<?php

declare(strict_types=1);

namespace DecisionsLint\Provider;

/**
 * Which decision API a run talks to and how.
 *
 * @property-read string $name jev, openai, ollama, localai or openai-compatible
 * @property-read string $model the model identifier the provider accepts
 * @property-read string $apiKeyEnv the environment variable that holds the API key
 * @property-read string $baseUrl the scheme and host of the API, without a trailing slash
 * @property-read int $timeout seconds to wait for one response
 * @property-read int $concurrency how many requests are in flight at once
 */
final class ProviderSettings
{
    /**
     * The TypeSafe AI decision model.
     */
    public const JEV = 'jev';

    /**
     * The OpenAI Decisions API.
     */
    public const OPENAI = 'openai';

    /**
     * A chat model served by Ollama through its native chat API.
     */
    public const OLLAMA = 'ollama';

    /**
     * A chat model served by LocalAI through its OpenAI-compatible API.
     */
    public const LOCALAI = 'localai';

    /**
     * Any server that speaks the OpenAI chat completions API with JSON schema output, such as vLLM or llama.cpp.
     */
    public const OPENAI_COMPATIBLE = 'openai-compatible';

    /**
     * Every supported provider.
     *
     * @var list<string>
     */
    public const NAMES = [self::JEV, self::OPENAI, self::OLLAMA, self::LOCALAI, self::OPENAI_COMPATIBLE];

    /**
     * Creates the immutable value.
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $model,
        /** @readonly */
        private string $apiKeyEnv,
        /** @readonly */
        private string $baseUrl,
        /** @readonly */
        private int $timeout = 60,
        /** @readonly */
        private int $concurrency = 8,
    ) {
    }

    /**
     * Returns the settings a provider uses when the configuration names only the provider.
     *
     * Hosted decision APIs answer quickly, so they get a short timeout and eight requests in flight.
     * Local chat models generate their answer token by token, so they get a long timeout and two.
     */
    public static function defaults(string $name): self
    {
        return match ($name) {
            self::OPENAI => new self(self::OPENAI, 'gpt-6-luna', 'OPENAI_API_KEY', 'https://api.openai.com'),
            self::OLLAMA => new self(self::OLLAMA, 'llama3.1', 'OLLAMA_API_KEY', 'http://localhost:11434', 300, 2),
            self::LOCALAI => new self(self::LOCALAI, 'gpt-4', 'LOCALAI_API_KEY', 'http://localhost:8080', 300, 2),
            self::OPENAI_COMPATIBLE => new self(self::OPENAI_COMPATIBLE, 'default', 'OPENAI_COMPATIBLE_API_KEY', 'http://localhost:8000', 300, 2),
            default => new self(self::JEV, 'jev-latest', 'TYPESAFE_API_KEY', 'https://api.typesafe.ai'),
        };
    }

    /**
     * Returns a declared immutable property.
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'name' => $this->name,
            'model' => $this->model,
            'apiKeyEnv' => $this->apiKeyEnv,
            'baseUrl' => $this->baseUrl,
            'timeout' => $this->timeout,
            'concurrency' => $this->concurrency,
            default => null,
        };
    }

    /**
     * Tells whether the provider refuses requests without an API key; local servers usually accept them.
     */
    public function requiresApiKey(): bool
    {
        return in_array($this->name, [self::JEV, self::OPENAI], true);
    }
}
