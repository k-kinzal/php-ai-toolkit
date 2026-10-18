<?php

declare(strict_types=1);

namespace DecisionsLint\Config;

use DecisionsLint\Diagnostic\LintException;

/**
 * Checks a decoded YAML document against one of the shipped JSON schemas.
 *
 * It implements the keywords those schemas use: $ref, allOf, type, const,
 * enum, required, properties, additionalProperties, propertyNames, items,
 * minItems, maxItems, minimum, maximum, minLength and pattern. The first
 * problem stops validation and is reported with its path and how to fix it.
 */
final class SchemaValidator
{
    /**
     * Creates a validator over the shipped schemas, or over another catalog.
     */
    public function __construct(private ?SchemaCatalog $catalog = null)
    {
    }

    /**
     * Validates a whole document against a named schema.
     *
     * @param mixed $document
     * @throws LintException when the document departs from the schema
     */
    public function validate($document, string $schemaName, string $file): void
    {
        $catalog = $this->catalog ?? new SchemaCatalog();
        $this->check($document, $catalog->schema($schemaName), $catalog->schema($schemaName), $catalog, $file, '');
    }

    /**
     * Validates one value against one schema node.
     *
     * @param mixed $value
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $root the schema document the node belongs to, for local references
     * @throws LintException when the value departs from the node
     */
    public function check($value, array $schema, array $root, SchemaCatalog $catalog, string $file, string $path): void
    {
        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            [$resolved, $resolvedRoot] = $this->resolve($schema['$ref'], $root, $catalog);
            $this->check($value, $resolved, $resolvedRoot, $catalog, $file, $path);
        }
        foreach ($this->list($schema['allOf'] ?? null) as $member) {
            $this->check($value, $this->object($member), $root, $catalog, $file, $path);
        }
        $this->checkScalar($value, $schema, $file, $path);
        if (is_array($value)) {
            $this->checkObject($value, $schema, $root, $catalog, $file, $path);
            $this->checkList($value, $schema, $root, $catalog, $file, $path);
        }
    }

    /**
     * Applies the keywords that concern the value itself: type, const, enum, bounds, length and pattern.
     *
     * @param mixed $value
     * @param array<string, mixed> $schema
     * @throws LintException when the value departs from the node
     */
    public function checkScalar($value, array $schema, string $file, string $path): void
    {
        if (isset($schema['type']) && !$this->hasType($value, $this->types($schema['type']))) {
            throw $this->problem($file, $path, sprintf('must be %s, not %s', $this->describeTypes($this->types($schema['type'])), $this->describe($value)), sprintf('Change the value to %s.', $this->describeTypes($this->types($schema['type']))));
        }
        if (array_key_exists('const', $schema) && $value !== $schema['const']) {
            throw $this->problem($file, $path, 'must be ' . $this->describe($schema['const']), sprintf('Set it to %s.', $this->describe($schema['const'])));
        }
        if (isset($schema['enum']) && !in_array($value, $this->list($schema['enum']), true)) {
            $allowed = implode(', ', array_map(fn ($entry): string => $this->describe($entry), $this->list($schema['enum'])));
            throw $this->problem($file, $path, 'must be one of ' . $allowed . ', not ' . $this->describe($value), 'Replace it with one of those values.');
        }
        if (is_int($value) || is_float($value)) {
            $this->checkBounds($value, $schema, $file, $path);
        }
        if (is_string($value)) {
            $this->checkString($value, $schema, $file, $path);
        }
    }

    /**
     * Applies minimum and maximum to a number.
     *
     * @param int|float $value
     * @param array<string, mixed> $schema
     * @throws LintException when the number is out of bounds
     */
    public function checkBounds($value, array $schema, string $file, string $path): void
    {
        if (isset($schema['minimum']) && is_numeric($schema['minimum']) && $value < $schema['minimum']) {
            throw $this->problem($file, $path, sprintf('must be at least %s, not %s', $this->describe($schema['minimum']), $this->describe($value)), 'Raise the value.');
        }
        if (isset($schema['maximum']) && is_numeric($schema['maximum']) && $value > $schema['maximum']) {
            throw $this->problem($file, $path, sprintf('must be at most %s, not %s', $this->describe($schema['maximum']), $this->describe($value)), 'Lower the value.');
        }
    }

    /**
     * Applies minLength and pattern to a string.
     *
     * @param array<string, mixed> $schema
     * @throws LintException when the string is too short or does not match
     */
    public function checkString(string $value, array $schema, string $file, string $path): void
    {
        if (isset($schema['minLength']) && is_int($schema['minLength']) && strlen($value) < $schema['minLength']) {
            throw $this->problem($file, $path, $schema['minLength'] === 1 ? 'must not be empty' : sprintf('must be at least %d characters long', $schema['minLength']), 'Write a value.');
        }
        if (isset($schema['pattern']) && is_string($schema['pattern']) && preg_match('#' . str_replace('#', '\#', $schema['pattern']) . '#', $value) !== 1) {
            throw $this->problem($file, $path, sprintf('must be %s, not %s', $this->expectation($schema, 'a value matching ' . $schema['pattern']), $this->describe($value)), 'Change the value.');
        }
    }

    /**
     * Applies required, properties, additionalProperties and propertyNames to a mapping.
     *
     * @param array<array-key, mixed> $value
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $root
     * @throws LintException when a key is missing, unknown, misnamed or invalid
     */
    public function checkObject(array $value, array $schema, array $root, SchemaCatalog $catalog, string $file, string $path): void
    {
        $properties = $this->object($schema['properties'] ?? null);
        foreach ($this->list($schema['required'] ?? null) as $required) {
            if (is_string($required) && !array_key_exists($required, $value)) {
                $description = $this->object($properties[$required] ?? null)['description'] ?? null;
                throw $this->problem($file, $path, sprintf('is missing the key "%s"', $required), 'Add it' . (is_string($description) ? ': ' . lcfirst($description) : '.'));
            }
        }
        if ($properties === [] && !isset($schema['additionalProperties']) && !isset($schema['propertyNames'])) {
            return;
        }
        foreach ($value as $key => $entry) {
            $key = (string) $key;
            $childPath = $path === '' ? $key : $path . '.' . $key;
            if (isset($properties[$key])) {
                $this->check($entry, $this->object($properties[$key]), $root, $catalog, $file, $childPath);
                continue;
            }
            if (isset($schema['propertyNames'])) {
                $this->checkPropertyName($key, $this->object($schema['propertyNames']), $root, $catalog, $file, $path);
            }
            $additional = $schema['additionalProperties'] ?? true;
            if ($additional === false) {
                throw $this->problem($file, $path, sprintf('has the unknown key "%s"', $key), 'Remove it or rename it to one of: ' . implode(', ', array_keys($properties)) . '.');
            }
            if (is_array($additional)) {
                $this->check($entry, $this->object($additional), $root, $catalog, $file, $childPath);
            }
        }
    }

    /**
     * Validates one key of a mapping against the propertyNames node, rephrasing the failure around the key.
     *
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $root
     * @throws LintException when the key is not allowed
     */
    public function checkPropertyName(string $key, array $schema, array $root, SchemaCatalog $catalog, string $file, string $path): void
    {
        try {
            $this->check($key, $schema, $root, $catalog, $file, $path);
        } catch (LintException $exception) {
            [$resolved] = isset($schema['$ref']) && is_string($schema['$ref']) ? $this->resolve($schema['$ref'], $root, $catalog) : [$schema, $root];
            throw $this->problem($file, $path, sprintf('has the key "%s", which is not %s', $key, $this->expectation($resolved, 'a valid key')), 'Rename the key.', $exception);
        }
    }

    /**
     * Applies minItems, maxItems and items to a list.
     *
     * @param array<array-key, mixed> $value
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $root
     * @throws LintException when the list has the wrong length or an invalid entry
     */
    public function checkList(array $value, array $schema, array $root, SchemaCatalog $catalog, string $file, string $path): void
    {
        if (isset($schema['minItems']) && is_int($schema['minItems']) && count($value) < $schema['minItems']) {
            throw $this->problem($file, $path, sprintf('must have at least %d %s, not %d', $schema['minItems'], $schema['minItems'] === 1 ? 'entry' : 'entries', count($value)), 'Add entries.');
        }
        if (isset($schema['maxItems']) && is_int($schema['maxItems']) && count($value) > $schema['maxItems']) {
            throw $this->problem($file, $path, sprintf('must have at most %d entries, not %d', $schema['maxItems'], count($value)), 'Remove entries.');
        }
        if (!isset($schema['items']) || array_values($value) !== $value) {
            return;
        }
        foreach ($value as $index => $entry) {
            $this->check($entry, $this->object($schema['items']), $root, $catalog, $file, $path . '[' . $index . ']');
        }
    }

    /**
     * Resolves a reference of the form #/$defs/name or file.schema.json#/$defs/name.
     *
     * @param array<string, mixed> $root
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} the referenced node and the document it lives in
     * @throws LintException when the reference points nowhere
     */
    public function resolve(string $reference, array $root, SchemaCatalog $catalog): array
    {
        [$document, $pointer] = array_pad(explode('#', $reference, 2), 2, '');
        if ($document !== '') {
            $root = $catalog->schema((string) preg_replace('/\.schema\.json$/', '', $document));
        }
        $node = $root;
        foreach (array_filter(explode('/', $pointer), static fn (string $segment): bool => $segment !== '') as $segment) {
            $node = $this->object($node[$segment] ?? null);
            if ($node === []) {
                throw new LintException(sprintf('Schema reference "%s" points to nothing. Reinstall decisions-lint so its schema directory is complete.', $reference));
            }
        }

        return [$node, $root];
    }

    /**
     * Tells whether a value has one of the JSON types; an empty array counts as both an object and an array.
     *
     * @param mixed $value
     * @param list<string> $types
     */
    public function hasType($value, array $types): bool
    {
        foreach ($types as $type) {
            $matches = match ($type) {
                'object' => is_array($value) && ($value === [] || array_values($value) !== $value),
                'array' => is_array($value) && array_values($value) === $value,
                'string' => is_string($value),
                'integer' => is_int($value),
                'number' => is_int($value) || is_float($value),
                'boolean' => is_bool($value),
                'null' => $value === null,
                default => false,
            };
            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns what a node expects, worded for a message: its description in lower case without a final period, or a fallback.
     *
     * @param array<string, mixed> $schema
     */
    public function expectation(array $schema, string $fallback): string
    {
        $description = $schema['description'] ?? null;

        return is_string($description) && $description !== '' ? lcfirst(rtrim($description, '.')) : $fallback;
    }

    /**
     * Builds the exception for one problem: the file, the path, what is wrong and what to do.
     */
    public function problem(string $file, string $path, string $what, string $fix, ?LintException $previous = null): LintException
    {
        return new LintException(sprintf('%s: %s %s. %s', $file, $path === '' ? 'the document' : $path, $what, $fix), 0, $previous);
    }

    /**
     * Describes a value for a message: strings quoted, scalars as PHP writes them, arrays by shape.
     *
     * @param mixed $value
     */
    public function describe($value): string
    {
        if (is_string($value)) {
            return '"' . $value . '"';
        }
        if (is_array($value)) {
            return $value === [] ? 'an empty list' : (array_values($value) === $value ? 'a list' : 'a mapping');
        }

        return var_export($value, true);
    }

    /**
     * Names one or more JSON types the way a reader would, such as "a string" or "true or false".
     *
     * @param list<string> $types
     */
    public function describeTypes(array $types): string
    {
        $names = [];
        foreach ($types as $type) {
            $names[] = match ($type) {
                'object' => 'a mapping',
                'array' => 'a list',
                'string' => 'a string',
                'integer' => 'an integer',
                'number' => 'a number',
                'boolean' => 'true or false',
                default => $type,
            };
        }

        return implode(' or ', $names);
    }

    /**
     * Reads a type keyword, which is one type or a list of types, as a list.
     *
     * @param mixed $type
     * @return list<string>
     */
    public function types($type): array
    {
        $types = [];
        foreach (is_array($type) ? $type : [$type] as $entry) {
            if (is_string($entry)) {
                $types[] = $entry;
            }
        }

        return $types;
    }

    /**
     * Reads a schema keyword that holds a mapping, or an empty mapping.
     *
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function object($value): array
    {
        $object = [];
        foreach (is_array($value) ? $value : [] as $key => $entry) {
            $object[(string) $key] = $entry;
        }

        return $object;
    }

    /**
     * Reads a schema keyword that holds a list, or an empty list.
     *
     * @param mixed $value
     * @return list<mixed>
     */
    public function list($value): array
    {
        return is_array($value) ? array_values($value) : [];
    }
}
