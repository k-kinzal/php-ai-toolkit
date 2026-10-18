<?php

declare(strict_types=1);

namespace DecisionsLint\Config;

use DecisionsLint\Provider\ProviderSettings;

/**
 * Turns the `provider` section into settings, filling in the defaults of the named provider.
 */
final class ProviderReader
{
    /**
     * Builds the settings; a missing section means Jev with its defaults.
     *
     * @param array<string, mixed> $provider
     */
    public function read(array $provider): ProviderSettings
    {
        $name = $provider['name'] ?? ProviderSettings::JEV;
        $defaults = ProviderSettings::defaults(is_string($name) ? $name : ProviderSettings::JEV);
        $model = $provider['model'] ?? null;
        $apiKeyEnv = $provider['api_key_env'] ?? null;
        $baseUrl = $provider['base_url'] ?? null;
        $timeout = $provider['timeout'] ?? null;
        $concurrency = $provider['concurrency'] ?? null;

        return new ProviderSettings(
            $defaults->name,
            is_string($model) ? $model : $defaults->model,
            is_string($apiKeyEnv) ? $apiKeyEnv : $defaults->apiKeyEnv,
            is_string($baseUrl) ? rtrim($baseUrl, '/') : $defaults->baseUrl,
            is_int($timeout) ? $timeout : $defaults->timeout,
            is_int($concurrency) ? $concurrency : $defaults->concurrency,
        );
    }
}
