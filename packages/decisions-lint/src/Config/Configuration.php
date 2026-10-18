<?php

declare(strict_types=1);

namespace DecisionsLint\Config;

use DecisionsLint\Provider\ProviderSettings;
use DecisionsLint\Rule\Rule;

/**
 * Everything a run needs, read from decisions.yaml and the rule files it imports.
 *
 * @property-read string $path the absolute path of decisions.yaml
 * @property-read string $directory the directory every path is relative to
 * @property-read ProviderSettings $provider the decision API to use
 * @property-read list<string> $paths the directories, files or globs to lint
 * @property-read list<string> $exclude the globs to leave out
 * @property-read string $failOn the weakest severity that fails the check, or never
 * @property-read list<Rule> $rules every rule, enabled or not, in definition order
 */
final class Configuration
{
    /**
     * Creates the immutable value.
     *
     * @param list<string> $paths
     * @param list<string> $exclude
     * @param list<Rule> $rules
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private ProviderSettings $provider,
        /** @readonly */
        private array $paths,
        /** @readonly */
        private array $exclude,
        /** @readonly */
        private string $failOn,
        /** @readonly */
        private array $rules,
    ) {
    }

    /**
     * Returns a declared immutable property.
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'path' => $this->path,
            'directory' => dirname($this->path),
            'provider' => $this->provider,
            'paths' => $this->paths,
            'exclude' => $this->exclude,
            'failOn' => $this->failOn,
            'rules' => $this->rules,
            default => null,
        };
    }

    /**
     * Returns the rules that run: the enabled ones, narrowed to the given ids when any are given.
     *
     * @param list<string> $ids
     * @return list<Rule>
     */
    public function activeRules(array $ids = []): array
    {
        $active = [];
        foreach ($this->rules as $rule) {
            if ($rule->enabled && ($ids === [] || in_array($rule->id, $ids, true))) {
                $active[] = $rule;
            }
        }

        return $active;
    }
}
