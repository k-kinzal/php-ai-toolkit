<?php

declare(strict_types=1);

namespace Toolkit\Guard\Config;

use JsonException;
use Toolkit\Guard\Document\Selection;
use Toolkit\Guard\Policy\Constraint;
use Toolkit\Guard\Policy\PolicyException;
use Toolkit\Guard\Policy\Rule;

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
        $data = $schema->mapping($entry, ['id', 'file', 'format', 'select', 'level', 'assert', 'repair'], 'configuration rule');
        $id = $schema->string($data['id'] ?? null, 'rule.id');
        $file = $schema->string($data['file'] ?? null, $id . '.file');
        $format = $schema->string($data['format'] ?? pathinfo($file, PATHINFO_EXTENSION), $id . '.format');
        if (!in_array($format, ['json', 'yaml', 'yml', 'xml', 'toml', 'neon'], true)) {
            throw new PolicyException($id . ': unsupported format "' . $format . '". Use json, yaml, xml, toml or neon.');
        }
        $select = $schema->string($data['select'] ?? null, $id . '.select');
        $level = $schema->string($data['level'] ?? 'required', $id . '.level');
        if (!in_array($level, ['required', 'recommended'], true)) {
            throw new PolicyException($id . '.level must be required or recommended.');
        }
        $assertions = $this->assertions($data['assert'] ?? null, $id);
        $repairable = array_key_exists('repair', $data) || array_key_exists('equals', $assertions);
        $repair = $data['repair'] ?? ($assertions['equals'] ?? null);
        if (array_key_exists('repair', $data)) {
            $repair = $data['repair'];
        }
        $candidate = $repair;
        if ($format === 'xml' && is_string($repair) && is_numeric($repair)
            && !array_key_exists('equals', $assertions) && !array_key_exists('one_of', $assertions)) {
            $candidate = (float) $repair;
        }
        if ($repairable && !(new Constraint())->accepts(new Selection(true, $candidate), $assertions)) {
            throw new PolicyException($id . ': repair must satisfy every assertion. Correct the repair or conflicting assertions.');
        }
        return new Rule($id, $file, $format, $select, $level, $assertions, $repair, $repairable);
    }
    /**
     * @param mixed $value
     * @return array<string, mixed>
     * @throws PolicyException when assertions are empty, contradictory or mistyped
     */
    public function assertions($value, string $id): array
    {
        $data = (new Schema())->mapping($value, ['equals', 'one_of', 'min', 'max'], $id . '.assert');
        if ($data === []) {
            throw new PolicyException($id . '.assert must contain equals, one_of, min or max.');
        }
        foreach (['min', 'max'] as $bound) {
            if (array_key_exists($bound, $data) && !is_int($data[$bound]) && !is_float($data[$bound])) {
                throw new PolicyException($id . '.assert.' . $bound . ' must be a number.');
            }
        }
        if (isset($data['min'], $data['max']) && $data['min'] > $data['max']) {
            throw new PolicyException($id . ': min must not exceed max.');
        }
        if (array_key_exists('one_of', $data)) {
            $choices = $data['one_of'];
            if (!is_array($choices) || $choices === [] || array_values($choices) !== $choices) {
                throw new PolicyException($id . '.assert.one_of must be a non-empty list.');
            }
        }
        return $data;
    }
}
