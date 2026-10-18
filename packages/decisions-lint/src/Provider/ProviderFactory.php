<?php

declare(strict_types=1);

namespace DecisionsLint\Provider;

use DecisionsLint\Decision\Decision;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\Transport;
use DecisionsLint\Provider\Jev\JevClient;
use DecisionsLint\Provider\Jev\JevDecision;
use DecisionsLint\Provider\Ollama\OllamaClient;
use DecisionsLint\Provider\Ollama\OllamaDecision;
use DecisionsLint\Provider\OpenAi\OpenAiClient;
use DecisionsLint\Provider\OpenAi\OpenAiDecision;
use DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsClient;
use DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsDecision;

/**
 * Builds the Decision adapter the configuration names, with the API key from the environment.
 */
final class ProviderFactory
{
    /**
     * Returns the adapter for the configured provider.
     *
     * @throws LintException when a required API key is not in the environment or the provider is unknown
     */
    public function create(ProviderSettings $settings, Transport $transport): Decision
    {
        $endpoint = new JsonEndpoint($transport, $settings, $this->apiKey($settings));

        return match ($settings->name) {
            ProviderSettings::JEV => new JevDecision(new JevClient($endpoint, $settings->model)),
            ProviderSettings::OPENAI => new OpenAiDecision(new OpenAiClient($endpoint, $settings->model)),
            ProviderSettings::OLLAMA => new OllamaDecision(new OllamaClient($endpoint, $settings->model)),
            ProviderSettings::LOCALAI, ProviderSettings::OPENAI_COMPATIBLE => new ChatCompletionsDecision(new ChatCompletionsClient($endpoint, $settings->model)),
            default => throw new LintException(sprintf('Unknown provider "%s". Set provider.name in decisions.yaml to one of: %s.', $settings->name, implode(', ', ProviderSettings::NAMES))),
        };
    }

    /**
     * Reads the API key from the configured environment variable; a provider that needs none gets an empty key when the variable is unset.
     *
     * @throws LintException when the variable is unset or empty and the provider needs a key
     */
    public function apiKey(ProviderSettings $settings): string
    {
        $key = getenv($settings->apiKeyEnv);
        if ($key === false || trim($key) === '') {
            if (!$settings->requiresApiKey()) {
                return '';
            }
            throw new LintException(sprintf('The %s environment variable is not set. Export the %s API key in it, or set provider.api_key_env in decisions.yaml to the variable that holds the key.', $settings->apiKeyEnv, $settings->name));
        }

        return trim($key);
    }
}
