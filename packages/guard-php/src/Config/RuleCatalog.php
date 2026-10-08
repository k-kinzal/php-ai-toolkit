<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Config\Reader\MetricPolicyReader;
use Guard\Policy\Diagnostic\RuleDescription;
use Guard\Policy\Diagnostic\RuleMessages;
use JsonException;

/**
 * Lists the effective policy after imports, overrides, defaults and inheritance.
 */
final class RuleCatalog
{
    /**
     * Diagnostic identifiers emitted by each document constraint.
     */
    private const DOCUMENT_RULES = [
        'headings' => ['unexpected_heading', 'missing_heading', 'renamed_heading', 'changed_heading_level', 'moved_heading'],
        'outlines' => ['unexpected_outline_heading', 'missing_outline_heading'],
        'badges' => ['missing_badges', 'unexpected_badge', 'missing_badge'],
        'content' => ['unexpected_content'],
    ];

    /**
     * @return list<RuleDescription>
     * @throws JsonException
     */
    public function load(string $path): array
    {
        /**
         * Validate the entire configuration even when a query selects only one rule.
         */
        $configuration = (new ConfigurationLoader())->load($path);
        $data = (new ImportResolver())->resolve($path);
        $rules = $this->fields($data['configuration'] ?? []);
        if (array_key_exists('metrics', $data)) {
            $rules = array_merge($rules, $this->metrics($data['metrics'], dirname($path)));
        }
        if (array_key_exists('structure', $data)) {
            $rules = array_merge($rules, $this->structure($data['structure']));
        }
        if (array_key_exists('documentation', $data)) {
            $rules = array_merge($rules, $this->documentation($data['documentation']));
        }
        foreach ($rules as $rule) {
            if ($configuration->scope !== null) {
                $rule->details['collect'] = ['include' => $configuration->scope->include, 'exclude' => $configuration->scope->exclude];
                if (str_starts_with($rule->id, 'metrics.')) {
                    $rule->target = '**/*.php within collect';
                    unset($rule->details['source'], $rule->details['exclude']);
                }
            }
        }
        foreach ((new Reader\ExtensionConfigReader())->read($data['extensions'] ?? []) as $class => $options) {
            $rules[] = new RuleDescription(
                'extension:' . $class,
                $class,
                'extension',
                false,
                'This configured policy or structurer defines its own behavior. Consult its documentation for diagnostics, remediation and repair capabilities.',
                $options
            );
        }
        return $rules;
    }

    /**
     * @return list<RuleDescription>
     * @throws JsonException
     */
    public function fields(mixed $value): array
    {
        $rules = [];
        foreach ((new RuleReader())->read($value) as $rule) {
            $details = ['format' => $rule->format, 'select' => $rule->select, 'assert' => $rule->assertions];
            if ($rule->repairable) {
                $details['repair'] = $rule->repair;
            }
            $rules[] = new RuleDescription(
                $rule->id,
                $rule->file,
                $rule->level,
                $rule->repairable,
                (new \Guard\Policy\Diagnostic\FieldMessage())->render($rule),
                $details
            );
        }
        return $rules;
    }

    /**
     * @return list<RuleDescription>
     */
    public function metrics(mixed $value, string $root): array
    {
        $config = (new MetricPolicyReader())->read($value, $root);
        $assignments = [];
        foreach ($config->apply->rules as $assignment) {
            $assignments[] = ['name' => $assignment->name, 'paths' => $assignment->paths, 'profile' => $assignment->policy];
        }
        $used = array_merge([$config->apply->defaultPolicy], array_column($assignments, 'profile'));
        $rules = [];
        foreach ($config->policies as $profile => $policy) {
            if (!in_array($profile, $used, true)) {
                continue;
            }
            foreach ($policy->limits->values() as $metric => $limit) {
                if ($limit === null) {
                    continue;
                }
                $id = str_ends_with($metric, '.cyclomatic_complexity') ? 'metrics.cyclomatic_complexity' : 'metrics.' . str_replace('.', '_', $metric);
                $rules[] = new RuleDescription(
                    $id,
                    implode(', ', $config->scan->roots),
                    'required',
                    false,
                    (new RuleMessages())->forRule($id),
                    ['profile' => $profile, 'metric' => $metric, 'max' => $limit, 'source' => $config->scan->roots, 'exclude' => $config->scan->exclude,
                        'default' => $config->apply->defaultPolicy, 'assignments' => $assignments]
                );
            }
        }
        return $rules;
    }

    /**
     * @return list<RuleDescription>
     */
    public function structure(mixed $value): array
    {
        $data = (new Schema())->mapping($value, ['paths', 'exclude', 'directories'], 'structure');
        $rules = [];
        $ids = ['allow' => 'disallowed_file', 'deny' => 'denied_file', 'allow_dirs' => 'disallowed_dir', 'deny_dirs' => 'denied_dir',
            'require' => 'missing_required_file', 'forbid_empty' => 'empty_directory'];
        $entries = $data['directories'] ?? [];
        if (!is_array($entries)) {
            return [];
        }
        foreach ($entries as $entry) {
            if (!is_array($entry) || !is_string($entry['path'] ?? null)) {
                continue;
            }
            foreach ($entry as $key => $constraint) {
                if (!is_string($key) || $key === 'path' || $constraint === null || $constraint === false) {
                    continue;
                }
                $id = 'structure.' . ($ids[$key] ?? $key);
                $rules[] = new RuleDescription(
                    $id,
                    $entry['path'],
                    'required',
                    false,
                    (new RuleMessages())->forRule($id),
                    [$key => $constraint, 'paths' => $data['paths'] ?? ['.'], 'exclude' => $data['exclude'] ?? []]
                );
            }
        }
        return $rules;
    }

    /**
     * @return list<RuleDescription>
     */
    public function documentation(mixed $value): array
    {
        $data = (new Schema())->mapping($value, ['files', 'scan', 'exclude'], 'documentation');
        $rules = [];
        $files = $data['files'] ?? [];
        if (!is_array($files)) {
            return [];
        }
        foreach ($files as $path => $settings) {
            if (!is_string($path) || !is_array($settings)) {
                continue;
            }
            $rules[] = new RuleDescription(
                'documentation.missing_document',
                $path,
                'required',
                false,
                (new RuleMessages())->forRule('documentation.missing_document')
            );
            foreach ($settings as $key => $constraint) {
                if (!is_string($key) || $key === 'max_level' || $constraint === null) {
                    continue;
                }
                $ids = self::DOCUMENT_RULES[$key] ?? [];
                foreach ($ids as $suffix) {
                    $id = 'documentation.' . $suffix;
                    $rules[] = new RuleDescription(
                        $id,
                        $path,
                        'required',
                        false,
                        (new RuleMessages())->forRule($id),
                        [$key => $constraint, 'max_level' => $settings['max_level'] ?? 6]
                    );
                }
            }
        }
        if (($data['scan'] ?? []) !== []) {
            $rules[] = new RuleDescription(
                'documentation.undeclared_document',
                implode(', ', (new Schema())->strings($data['scan'], 'documentation.scan')),
                'required',
                false,
                (new RuleMessages())->forRule('documentation.undeclared_document'),
                ['exclude' => $data['exclude'] ?? []]
            );
        }
        return $rules;
    }
}
