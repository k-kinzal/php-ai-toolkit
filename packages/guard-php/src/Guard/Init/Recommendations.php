<?php

declare(strict_types=1);

namespace Toolkit\Guard\Init;

use JsonException;

/**
 * Builds strict tool-specific field constraints for installed development tools.
 */
final class Recommendations
{
    /**
     * @return list<array<string, mixed>>
     * @throws JsonException
     */
    public function rules(string $root): array
    {
        $packages = (new ToolDetector())->packages($root);
        $rules = [];
        if (isset($packages['phpstan/phpstan']) || isset($packages['k-kinzal/phpstan-guard-rules'])) {
            $path = $this->existing($root, ['phpstan.neon', 'phpstan.neon.dist']);
            if ($path !== null) {
                $rules[] = $this->exact('phpstan.level', $path, 'neon', '/parameters/level', 'max');
                if (isset($packages['k-kinzal/phpstan-guard-rules']) || isset($packages['k-kinzal/php-ai-toolkit'])) {
                    $rules[] = $this->exact('phpstan.all-rules', $path, 'neon', '/parameters/toolkit/allRules', true);
                }
            }
        }
        if (isset($packages['phpunit/phpunit'])) {
            $path = $this->existing($root, ['phpunit.xml', 'phpunit.xml.dist']);
            if ($path !== null) {
                $rules = array_merge($rules, $this->phpunit($path, $packages['phpunit/phpunit']));
            }
        }
        if (is_file($root . '/composer.json')) {
            $rules[] = $this->exact('composer.sort-packages', 'composer.json', 'json', '/config/sort-packages', true, 'recommended');
        }
        return $rules;
    }
    /**
     * @return list<array<string, mixed>>
     */
    public function phpunit(string $file, string $version): array
    {
        $rules = [];
        foreach (['beStrictAboutChangesToGlobalState', 'beStrictAboutOutputDuringTests', 'beStrictAboutTestsThatDoNotTestAnything', 'enforceTimeLimit'] as $attribute) {
            $rules[] = $this->exact('phpunit.' . $attribute, $file, 'xml', '/phpunit/@' . $attribute, 'true');
        }
        $legacy = preg_match('/^[~^]?9(?:\.|$)/', $version) === 1;
        $attributes = $legacy ? ['failOnWarning', 'failOnRisky', 'failOnIncomplete', 'failOnSkipped'] : ['failOnAllIssues', 'requireCoverageMetadata', 'beStrictAboutCoverageMetadata'];
        foreach ($attributes as $attribute) {
            $rules[] = $this->exact('phpunit.' . $attribute, $file, 'xml', '/phpunit/@' . $attribute, 'true');
        }
        return $rules;
    }
    /**
     * @param list<string> $candidates
     */
    public function existing(string $root, array $candidates): ?string
    {
        foreach ($candidates as $file) {
            if (is_file($root . '/' . $file)) {
                return $file;
            }
        }
        return null;
    }
    /**
     * @param bool|string $value
     * @return array<string, mixed>
     */
    public function exact(string $id, string $file, string $format, string $select, $value, string $level = 'required'): array
    {
        return ['id' => $id, 'file' => $file, 'format' => $format, 'select' => $select, 'level' => $level, 'assert' => ['equals' => $value]];
    }
}
