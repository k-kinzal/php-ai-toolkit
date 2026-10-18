<?php

declare(strict_types=1);

namespace DecisionsLint\Reporting;

use DecisionsLint\Rule\Rule;
use JsonException;

/**
 * Lists the effective rules after imports and overrides, as text or JSON.
 */
final class RuleCatalogReporter
{
    /**
     * Returns the rules in the requested format; text for every format but json.
     *
     * @param list<Rule> $rules
     * @throws JsonException when a rule holds text that cannot be encoded
     */
    public function render(array $rules, string $format): string
    {
        if ($format === 'json') {
            $rows = [];
            foreach ($rules as $rule) {
                $rows[] = $this->row($rule);
            }

            return json_encode(['rules' => $rows, 'count' => count($rows)], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        }
        $lines = [];
        foreach ($rules as $rule) {
            $lines[] = sprintf('%s  %s  %s %s  %s%s', $rule->id, $rule->severity, $rule->target->kind, $rule->decision->type, $rule->enabled ? 'enabled' : 'disabled', $rule->paths === [] ? '' : '  ' . implode(',', $rule->paths));
            if ($rule->description !== '') {
                $lines[] = '  ' . $rule->description;
            }
        }
        $lines[] = sprintf('%d %s.', count($rules), count($rules) === 1 ? 'rule' : 'rules');

        return implode("\n", $lines) . "\n";
    }

    /**
     * Returns the JSON row of one rule.
     *
     * @return array<string, mixed>
     */
    public function row(Rule $rule): array
    {
        $violations = [];
        foreach ($rule->violations as $violation) {
            $violations[] = ['answer' => $violation->answer, 'min_score' => $violation->minScore, 'max_score' => $violation->maxScore, 'message' => $violation->message, 'fix' => $violation->fix];
        }

        return [
            'id' => $rule->id,
            'description' => $rule->description,
            'severity' => $rule->severity,
            'enabled' => $rule->enabled,
            'paths' => $rule->paths,
            'exclude' => $rule->exclude,
            'target' => ['kind' => $rule->target->kind, 'visibility' => $rule->target->visibility, 'names' => $rule->target->names, 'exclude_names' => $rule->target->excludeNames, 'static' => $rule->target->static],
            'context' => $rule->context,
            'decision' => ['type' => $rule->decision->type, 'instructions' => $rule->decision->instructions, 'choices' => $rule->decision->choices, 'levels' => $rule->decision->levels, 'threshold' => $rule->decision->threshold],
            'violations' => $violations,
        ];
    }
}
