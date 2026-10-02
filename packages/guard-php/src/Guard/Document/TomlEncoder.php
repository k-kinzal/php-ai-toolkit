<?php

declare(strict_types=1);

namespace Toolkit\Guard\Document;

use DateTimeInterface;
use JsonException;
use stdClass;
use Toolkit\Guard\Policy\PolicyException;

/**
 * Encodes supported TOML data without coercing null or losing datetime values.
 */
final class TomlEncoder
{
    /**
     * @param mixed $data
     * @throws PolicyException when a document root is not a table
     * @throws JsonException
     */
    public function encode($data): string
    {
        if (!$data instanceof stdClass && !is_array($data)) {
            throw new PolicyException('TOML root must be a table.');
        }
        $lines = [];
        foreach ((array) $data as $key => $value) {
            $lines[] = $this->quote((string) $key) . ' = ' . $this->value($value);
        }
        return implode("\n", $lines) . "\n";
    }
    /**
     * @param mixed $value
     * @throws PolicyException when TOML cannot represent a value
     * @throws JsonException
     */
    public function value($value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d\TH:i:sP');
        }
        if ($value instanceof stdClass || (is_array($value) && $value !== [] && array_values($value) !== $value)) {
            $entries = [];
            foreach ((array) $value as $key => $entry) {
                $entries[] = $this->quote((string) $key) . ' = ' . $this->value($entry);
            }
            return '{ ' . implode(', ', $entries) . ' }';
        }
        if (is_array($value)) {
            $entries = [];
            foreach ($value as $entry) {
                $entries[] = $this->value($entry);
            }
            return '[' . implode(', ', $entries) . ']';
        }
        if (is_string($value)) {
            return $this->quote($value);
        }
        if (is_int($value) || is_float($value) || is_bool($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        }
        throw new PolicyException('TOML cannot encode this value. Use a string, boolean, number, date, array or table; null is unsupported.');
    }
    /**
     * Quotes a TOML basic string with JSON-compatible escapes.
     * @throws JsonException
     */
    public function quote(string $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
