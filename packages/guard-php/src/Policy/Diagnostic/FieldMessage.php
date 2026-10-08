<?php

declare(strict_types=1);

namespace Guard\Policy\Diagnostic;

use Guard\Diagnostic\PolicyException;
use Guard\Policy\Rule;
use JsonException;

/**
 * Describes a field violation and a concrete edit using the effective constraints.
 */
final class FieldMessage
{
    /**
     * Expands optional diagnostic templates after imports and overrides are resolved.
     * @throws JsonException
     */
    public function render(Rule $rule, bool $includeFixCommand = true): string
    {
        $expectations = [];
        foreach ($rule->assertions as $kind => $value) {
            $expectations[] = $this->expectation($kind, $value);
        }
        $template = $rule->message === '' ? '{select} in {file} must {expectation}. {fix}' : $rule->message;
        $message = strtr($template, ['{file}' => $rule->file, '{select}' => $rule->select,
            '{expectation}' => implode(' and ', $expectations), '{fix}' => $this->fix($rule)]);
        return $message . ($rule->repairable && $includeFixCommand ? ' Run guard fix to apply the configured repair.' : '');
    }

    /**
     * Describes the expected value without disclosing the current configuration value.
     * @throws JsonException
     * @throws PolicyException
     */
    public function expectation(string $kind, mixed $value): string
    {
        $quoted = $this->quote($value);
        return match ($kind) {
            'equals' => 'equal ' . $quoted,
            'one_of' => 'be one of ' . $quoted,
            'min' => 'be at least ' . $quoted,
            'max' => 'be at most ' . $quoted,
            'contains' => 'include ' . $quoted,
            'contains_any' => 'include at least one of ' . $quoted,
            'not_contains' => 'exclude ' . $quoted,
            'present' => 'exist',
            'absent' => 'be absent',
            default => throw new PolicyException('Unknown assertion ' . $kind . '. Use a supported configuration assertion.'),
        };
    }

    /**
     * Names the actual edit, including manual edits for PHP and JSON5 documents.
     * @throws JsonException
     */
    public function fix(Rule $rule): string
    {
        $assertions = $rule->assertions;
        $target = $rule->select . ' in ' . $rule->file;
        if ($rule->repairable) {
            return 'Set ' . $target . ' to ' . $this->quote($rule->repair) . '.';
        }
        if (array_key_exists('equals', $assertions)) {
            $value = $this->quote($assertions['equals']);
            if ($rule->format === 'php') {
                return $rule->select === '/riskyAllowed'
                    ? 'Call setRiskyAllowed(' . $value . ') in ' . $rule->file . '.'
                    : 'Set the ' . substr($rule->select, strlen('/rules/')) . ' option to ' . $value . ' in the literal array passed to setRules() in ' . $rule->file . '.';
            }
            return 'Set ' . $target . ' to ' . $value . ' manually.';
        }
        $steps = [];
        foreach ($assertions as $kind => $value) {
            $steps[] = $this->step($kind, $value, $target);
        }
        return implode(' ', $steps);
    }

    /**
     * Describes a manual correction when no single repair value is configured.
     * @throws JsonException
     * @throws PolicyException
     */
    public function step(string $kind, mixed $value, string $target): string
    {
        $quoted = $this->quote($value);
        return match ($kind) {
            'one_of' => 'Set ' . $target . ' to one of ' . $quoted . '.',
            'min' => 'Set ' . $target . ' to a number greater than or equal to ' . $quoted . '.',
            'max' => 'Set ' . $target . ' to a number less than or equal to ' . $quoted . '.',
            'contains' => 'Add ' . $quoted . ' to ' . $target . ', preserving its other entries or text.',
            'contains_any' => 'Add at least one of ' . $quoted . ' to ' . $target . ', preserving its other entries or text.',
            'not_contains' => 'Remove ' . $quoted . ' from ' . $target . ', preserving unrelated content.',
            'present' => 'Add the missing field ' . $target . '.',
            'absent' => 'Remove the field ' . $target . '.',
            default => throw new PolicyException('Unknown manual assertion ' . $kind . '. Use a supported configuration assertion.'),
        };
    }

    /**
     * Preserves the expected value type in diagnostics.
     * @throws JsonException
     */
    public function quote(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }
}
