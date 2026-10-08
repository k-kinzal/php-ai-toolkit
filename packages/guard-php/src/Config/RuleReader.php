<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Document\Selection;
use Guard\Policy\Constraint;
use Guard\Policy\PolicyException;
use Guard\Policy\Rule;
use JsonException;

/**
 * Validates every rule before any target document is opened.
 */
final class RuleReader
{
    /**
     * @param mixed $value
     * @return list<Rule>
     * @throws PolicyException when rule IDs are duplicated or invalid
     * @throws JsonException
     */
    public function read($value): array
    {
        if (!is_array($value) || array_values($value) !== $value) {
            throw new PolicyException('configuration must be a list of field rules.');
        }
        $rules = [];
        $seen = [];
        foreach ($value as $entry) {
            $rule = $this->rule($entry);
            if (isset($seen[$rule->id])) {
                throw new PolicyException('Duplicate configuration rule "' . $rule->id . '". Give each rule a unique id.');
            }
            $seen[$rule->id] = true;
            $rules[] = $rule;
        }
        return $rules;
    }
    /**
     * @param mixed $entry
     * @throws PolicyException when a rule is malformed or its repair violates its constraints
     * @throws JsonException
     */
    public function rule($entry): Rule
    {
        $schema = new Schema();
        $data = $schema->mapping($entry, ['id', 'file', 'format', 'select', 'level', 'assert', 'repair', 'message'], 'configuration rule');
        $id = $schema->string($data['id'] ?? null, 'rule.id');
        $file = $schema->string($data['file'] ?? null, $id . '.file');
        $format = $this->format($schema->string($data['format'] ?? pathinfo($file, PATHINFO_EXTENSION), $id . '.format'), $id);
        $select = $schema->string($data['select'] ?? null, $id . '.select');
        $level = $this->level($schema->string($data['level'] ?? 'required', $id . '.level'), $id);
        $assertions = $this->assertions($data['assert'] ?? null, $id);
        $repairable = $this->repairable($format, $data, $assertions);
        $repair = $data['repair'] ?? ($assertions['equals'] ?? null);
        if (array_key_exists('repair', $data)) {
            $repair = $data['repair'];
        }
        $candidate = $this->repairValue($format, $assertions, $repair);
        if ($repairable && !(new Constraint())->accepts(new Selection(true, $candidate), $assertions)) {
            throw new PolicyException($id . ': repair must satisfy every assertion. Correct the repair or conflicting assertions.');
        }
        $message = array_key_exists('message', $data) ? $schema->string($data['message'], $id . '.message') : '';
        if (array_key_exists('message', $data) && trim($message) === '') {
            throw new PolicyException($id . '.message must describe the problem and how to fix it.');
        }
        return new Rule($id, $file, $format, $select, $level, $assertions, $repair, $repairable, $message);
    }

    /**
     * Accepts a document format Guard can read.
     *
     * @throws PolicyException when the format is unsupported
     */
    public function format(string $format, string $id): string
    {
        if (!in_array($format, ['json', 'yaml', 'yml', 'xml', 'toml', 'neon', 'json5', 'php'], true)) {
            throw new PolicyException($id . ': unsupported format "' . $format . '". Use json, yaml, xml, toml, neon, json5 or php.');
        }

        return $format;
    }

    /**
     * Accepts required or recommended.
     *
     * @throws PolicyException when the level is neither
     */
    public function level(string $level, string $id): string
    {
        if (!in_array($level, ['required', 'recommended'], true)) {
            throw new PolicyException($id . '.level must be required or recommended.');
        }

        return $level;
    }

    /**
     * Reports whether Guard may write the repair. PHP and JSON5 stay check-only.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $assertions
     */
    public function repairable(string $format, array $data, array $assertions): bool
    {
        return !in_array($format, ['php', 'json5'], true)
            && (array_key_exists('repair', $data) || array_key_exists('equals', $assertions));
    }

    /**
     * Coerces a numeric XML repair when the assertions are bounds rather than exact text.
     *
     * @param array<string, mixed> $assertions
     * @param mixed $repair
     * @return mixed
     */
    public function repairValue(string $format, array $assertions, $repair): mixed
    {
        if ($format === 'xml' && is_string($repair) && is_numeric($repair)
            && !array_key_exists('equals', $assertions) && !array_key_exists('one_of', $assertions)) {
            return (float) $repair;
        }

        return $repair;
    }
    /**
     * @param mixed $value
     * @return array<string, mixed>
     * @throws PolicyException when assertions are empty, contradictory or mistyped
     */
    public function assertions($value, string $id): array
    {
        $keys = ['equals', 'one_of', 'min', 'max', 'contains', 'contains_any', 'not_contains', 'present', 'absent'];
        $data = (new Schema())->mapping($value, $keys, $id . '.assert');
        if ($data === []) {
            throw new PolicyException($id . '.assert must contain equals, one_of, min, max, contains, contains_any, not_contains, present or absent.');
        }
        $this->bounds($data, $id);
        $this->choices($data, $id);
        $this->texts($data, $id);
        $this->flags($data, $id);

        return $data;
    }

    /**
     * Checks numeric bounds.
     *
     * @param array<string, mixed> $data
     * @throws PolicyException when a bound is not numeric or the range is empty
     */
    public function bounds(array $data, string $id): void
    {
        foreach (['min', 'max'] as $bound) {
            if (array_key_exists($bound, $data) && !is_int($data[$bound]) && !is_float($data[$bound])) {
                throw new PolicyException($id . '.assert.' . $bound . ' must be a number.');
            }
        }
        if (isset($data['min'], $data['max']) && $data['min'] > $data['max']) {
            throw new PolicyException($id . ': min must not exceed max.');
        }
    }

    /**
     * Checks that one_of is a non-empty list.
     *
     * @param array<string, mixed> $data
     * @throws PolicyException when one_of is not a list
     */
    public function choices(array $data, string $id): void
    {
        if (!array_key_exists('one_of', $data)) {
            return;
        }
        $choices = $data['one_of'];
        if (!is_array($choices) || $choices === [] || array_values($choices) !== $choices) {
            throw new PolicyException($id . '.assert.one_of must be a non-empty list.');
        }
    }

    /**
     * Checks string needles used by partial matches.
     *
     * @param array<string, mixed> $data
     * @throws PolicyException when a needle is empty or not a string
     */
    public function texts(array $data, string $id): void
    {
        foreach (['contains', 'not_contains'] as $kind) {
            if (array_key_exists($kind, $data) && (!is_string($data[$kind]) || $data[$kind] === '')) {
                throw new PolicyException($id . '.assert.' . $kind . ' must be a non-empty string.');
            }
        }
        if (!array_key_exists('contains_any', $data)) {
            return;
        }
        $needles = $data['contains_any'];
        if (!is_array($needles) || $needles === [] || array_values($needles) !== $needles) {
            throw new PolicyException($id . '.assert.contains_any must be a non-empty list of strings.');
        }
        foreach ($needles as $needle) {
            if (!is_string($needle) || $needle === '') {
                throw new PolicyException($id . '.assert.contains_any must be a non-empty list of strings.');
            }
        }
    }

    /**
     * Checks presence flags.
     *
     * @param array<string, mixed> $data
     * @throws PolicyException when a flag is not true or absent is combined with another assertion
     */
    public function flags(array $data, string $id): void
    {
        foreach (['present', 'absent'] as $flag) {
            if (array_key_exists($flag, $data) && $data[$flag] !== true) {
                throw new PolicyException($id . '.assert.' . $flag . ' must be true.');
            }
        }
        if (array_key_exists('absent', $data) && count($data) !== 1) {
            throw new PolicyException($id . '.assert.absent cannot be combined with another assertion.');
        }
    }
}
