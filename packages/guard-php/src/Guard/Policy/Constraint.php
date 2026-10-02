<?php

declare(strict_types=1);

namespace Toolkit\Guard\Policy;

use JsonException;
use stdClass;
use Toolkit\Guard\Document\Selection;

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
