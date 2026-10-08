<?php

declare(strict_types=1);

namespace Guard\Structure\Document;

use JsonException;
use stdClass;

/**
 * Evaluates exact values, allowed alternatives and numeric bounds together.
 */
final class Constraint
{
    /**
     * @param array<string, mixed> $assertions
     * @throws JsonException
     */
    public function accepts(Selection $selected, array $assertions): bool
    {
        if (array_key_exists('absent', $assertions)) {
            return $assertions['absent'] === true && !$selected->exists;
        }
        if (!$selected->exists) {
            return false;
        }
        $value = $selected->value;
        foreach ($assertions as $kind => $expected) {
            if (!$this->acceptsOne($kind, $value, $expected)) {
                return false;
            }
        }
        return true;
    }
    /**
     * @param mixed $value
     * @param mixed $expected
     * @throws JsonException
     */
    public function acceptsOne(string $kind, $value, $expected): bool
    {
        return match ($kind) {
            'equals' => $this->equal($value, $expected),
            'one_of' => is_array($expected) && $this->oneOf($value, array_values($expected)),
            'min' => (is_int($value) || is_float($value)) && $value >= $expected,
            'max' => (is_int($value) || is_float($value)) && $value <= $expected,
            'contains' => $this->contains($value, $expected),
            'contains_any' => $this->containsAny($value, $expected),
            'not_contains' => $this->notContains($value, $expected),
            'present' => $expected === true,
            'absent' => false,
            default => false,
        };
    }
    /**
     * @param mixed $value
     * @param list<mixed> $expected
     * @throws JsonException
     */
    public function oneOf($value, array $expected): bool
    {
        foreach ($expected as $choice) {
            if ($this->equal($value, $choice)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Reports whether a string, list, or nested document contains the needle.
     *
     * @param mixed $value
     * @param mixed $needle
     */
    public function contains($value, $needle): bool
    {
        if (!is_string($needle) || $needle === '') {
            return false;
        }
        if (is_string($value)) {
            return str_contains($value, $needle);
        }
        if (is_object($value)) {
            $value = get_object_vars($value);
        }
        if (!is_array($value)) {
            return false;
        }
        if ($this->pair($value, $needle)) {
            return true;
        }
        foreach ($value as $item) {
            if ($this->contains($item, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reports whether any needle matches a list entry exactly, or as a path fragment when it contains a slash.
     *
     * @param mixed $value
     * @param mixed $needles
     */
    public function containsAny($value, $needles): bool
    {
        if (!is_array($needles)) {
            return false;
        }
        foreach ($needles as $needle) {
            if (is_string($needle) && $this->matches($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reports whether the needle is absent from the selected document.
     *
     * @param mixed $value
     * @param mixed $needle
     */
    public function notContains($value, $needle): bool
    {
        return is_string($needle) && $needle !== '' && !$this->contains($value, $needle);
    }

    /**
     * Matches one include entry without treating a short name as a suffix of every path.
     *
     * @param mixed $value
     */
    public function matches($value, string $needle): bool
    {
        if (is_string($value)) {
            return $value === $needle || (str_contains($needle, '/') && str_contains($value, $needle));
        }
        if (is_object($value)) {
            $value = get_object_vars($value);
        }
        if (!is_array($value)) {
            return false;
        }
        foreach ($value as $item) {
            if ($this->matches($item, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Matches a map entry written as key=value.
     *
     * @param array<mixed> $value
     */
    public function pair(array $value, string $needle): bool
    {
        $list = $value === [] || array_keys($value) === range(0, count($value) - 1);
        $equals = strpos($needle, '=');
        if ($list || $equals === false || $equals === 0) {
            return false;
        }
        $key = substr($needle, 0, $equals);
        if (!array_key_exists($key, $value)) {
            return false;
        }
        $actual = $value[$key];

        return is_string($actual) && $actual === substr($needle, $equals + 1);
    }

    /**
     * Compares data with scalar types and object/list distinctions intact.
     * @param mixed $left
     * @param mixed $right
     * @throws JsonException
     */
    public function equal($left, $right): bool
    {
        return json_encode($this->canonical($left), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION)
            === json_encode($this->canonical($right), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }
    /**
     * Normalizes map order without conflating lists with objects.
     * @param mixed $value
     * @return mixed
     */
    public function canonical($value): mixed
    {
        if ($value instanceof stdClass) {
            $entries = get_object_vars($value);
            ksort($entries);
            foreach ($entries as $key => $entry) {
                $entries[$key] = $this->canonical($entry);
            }
            return (object) $entries;
        }
        if (is_array($value)) {
            $list = $value === [] || array_keys($value) === range(0, count($value) - 1);
            if (!$list) {
                ksort($value);
            }
            foreach ($value as $key => $entry) {
                $value[$key] = $this->canonical($entry);
            }
        }
        return $value;
    }
}
