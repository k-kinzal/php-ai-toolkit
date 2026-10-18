<?php

declare(strict_types=1);

namespace DecisionsLint\Provider;

/**
 * Which decision API a run talks to and how.
 *
 * @property-read string $name jev or openai
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
     * Every supported provider.
     *
     * @var list<string>
     */
    public const NAMES = [self::JEV, self::OPENAI];

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
     */
    public static function defaults(string $name): self
    {
        return $name === self::OPENAI
            ? new self(self::OPENAI, 'gpt-6-luna', 'OPENAI_API_KEY', 'https://api.openai.com')
            : new self(self::JEV, 'jev-latest', 'TYPESAFE_API_KEY', 'https://api.typesafe.ai');
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
}
