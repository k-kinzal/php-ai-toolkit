<?php

declare(strict_types=1);

namespace DecisionsLint\Provider;

use DecisionsLint\Diagnostic\LintException;

/**
 * Reads typed fields out of a decoded provider response, naming the field when it is missing or malformed.
 */
final class Payload
{
    /**
     * Creates a reader for one decoded document described by a context such as "Jev response.answers".
     *
     * @param array<array-key, mixed> $data
     */
    public function __construct(
        /**
         * The decoded JSON document, as json_decode returns it, with numeric object keys as integers.
         *
         * @var array<array-key, mixed>
         */
        public array $data,
        private string $context,
    ) {
    }

    /**
     * Reads a number.
     *
     * @throws LintException when the field is missing or not a number
     */
    public function float(string $key): float
    {
        $value = $this->data[$key] ?? null;
        if (!is_int($value) && !is_float($value)) {
            throw new LintException($this->problem($key, 'a number', $value));
        }

        return (float) $value;
    }

    /**
     * Reads a string.
     *
     * @throws LintException when the field is missing or not a string
     */
    public function string(string $key): string
    {
        $value = $this->data[$key] ?? null;
        if (!is_string($value)) {
            throw new LintException($this->problem($key, 'a string', $value));
        }

        return $value;
    }

    /**
     * Reads a string, or returns a default when the field is absent.
     */
    public function stringOr(string $key, string $default): string
    {
        $value = $this->data[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * Reads a mapping or list of values as a nested payload.
     *
     * @throws LintException when the field is missing or not an array
     */
    public function nested(string $key): self
    {
        $value = $this->data[$key] ?? null;
        if (!is_array($value)) {
            throw new LintException($this->problem($key, 'a mapping or list', $value));
        }

        return new self($value, $this->context . '.' . $key);
    }

    /**
     * Reads a mapping of names to numbers, such as a probability distribution.
     *
     * @return array<array-key, float>
     * @throws LintException when the field is missing or a value is not a number
     */
    public function floats(string $key): array
    {
        $nested = $this->nested($key);
        $floats = [];
        foreach ($nested->keys() as $name) {
            $floats[$name] = $nested->float($name);
        }

        return $floats;
    }

    /**
     * Reads a list of mappings as nested payloads.
     *
     * @return list<self>
     * @throws LintException when the field is missing or an entry is not a mapping
     */
    public function entries(string $key): array
    {
        $nested = $this->nested($key);
        $entries = [];
        foreach ($nested->keys() as $index) {
            $entries[] = $nested->nested($index);
        }

        return $entries;
    }

    /**
     * Returns the keys of the document.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map('strval', array_keys($this->data));
    }

    /**
     * Tells whether a field is present.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Describes a field that does not hold what the API promised.
     *
     * @param mixed $value
     */
    public function problem(string $key, string $expected, $value): string
    {
        $actual = $value === null ? 'it is missing' : 'it is ' . (is_scalar($value) ? var_export($value, true) : gettype($value));

        return sprintf('%s: field "%s" must be %s, but %s. The provider returned a response this version does not understand; report the response or update decisions-lint.', $this->context, $key, $expected, $actual);
    }
}
