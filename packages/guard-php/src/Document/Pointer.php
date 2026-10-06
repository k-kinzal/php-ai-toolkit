<?php

declare(strict_types=1);

namespace Guard\Document;

use Guard\Policy\PolicyException;
use JsonException;
use stdClass;

/**
 * Selects a document field with RFC 6901 JSON Pointer syntax.
 */
final class Pointer
{
    /**
     * @return list<string>
     * @throws PolicyException when a pointer is malformed
     */
    public function tokens(string $pointer): array
    {
        if (!str_starts_with($pointer, '/') || preg_match('/~(?![01])/', $pointer) === 1) {
            throw new PolicyException('Invalid pointer "' . $pointer . '". Use /section/key and escape ~ as ~0 and / as ~1.');
        }
        return array_map(static fn (string $part): string => str_replace(['~1', '~0'], ['/', '~'], $part), explode('/', substr($pointer, 1)));
    }
    /**
     * @param mixed $data
     * @throws JsonException
     */
    public function read($data, string $pointer): Selection
    {
        foreach ($this->tokens($pointer) as $key) {
            if ($data instanceof stdClass && property_exists($data, $key)) {
                $data = $data->{$key};
            } elseif (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                return new Selection(false, null);
            }
        }
        return new Selection(true, $data);
    }
    /**
     * @param mixed $data
     * @param mixed $value
     * @return mixed
     */
    public function write($data, string $pointer, $value): mixed
    {
        return $this->replace($data, $this->tokens($pointer), $value);
    }
    /**
     * Creates missing object fields while refusing to replace scalar parents or extend arrays.
     * @param mixed $data
     * @param list<string> $tokens
     * @param mixed $value
     * @return mixed
     * @throws PolicyException when an intermediate value is not a container
     */
    public function replace($data, array $tokens, $value): mixed
    {
        if ($tokens === []) {
            return $value;
        }
        $key = array_shift($tokens);
        if ($data instanceof stdClass) {
            $data->{$key} = $this->replace(property_exists($data, $key) ? $data->{$key} : new stdClass(), $tokens, $value);
            return $data;
        }
        if (is_array($data)) {
            $list = $data === [] || array_keys($data) === range(0, count($data) - 1);
            if ($list && !array_key_exists($key, $data)) {
                throw new PolicyException('Array index "' . $key . '" does not exist. Select an existing index.');
            }
            $data[$key] = $this->replace(array_key_exists($key, $data) ? $data[$key] : new stdClass(), $tokens, $value);
            return $data;
        }
        throw new PolicyException('Cannot descend through a scalar at "' . $key . '". Correct the selector or document structure.');
    }
}
