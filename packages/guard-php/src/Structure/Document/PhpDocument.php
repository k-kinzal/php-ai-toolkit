<?php

declare(strict_types=1);

namespace Guard\Structure\Document;

use Guard\Diagnostic\PolicyException;
use JsonException;
use PhpToken;
use stdClass;

/**
 * Checks and sets literal PHP configuration values without executing the file.
 */
final class PhpDocument
{
    private DocumentNode $data;
    private bool $arrayConfiguration;
    /**
     * @param ?list<PhpToken> $tokens
     * Keeps the original PHP source. Literal calls are structured during collection.
     */
    public function __construct(private string $source, ?array $tokens = null)
    {
        $configuration = (new PhpConfigReader())->configuration($tokens ?? array_values(PhpToken::tokenize($source)));
        $this->arrayConfiguration = $configuration['array'];
        $this->data = new DocumentNode($configuration['data']);
    }

    /**
     * Reads a field from the literal risky flag or rule map.
     *
     * @throws JsonException when the selected value cannot be compared
     */
    public function read(string $selector): Selection
    {
        return (new Pointer())->read($this->data->native(), $selector);
    }

    /**
     * Sets a literal configuration value, preserving unrelated PHP statements.
     *
     * @param mixed $value
     * @throws PolicyException when a selector does not identify a supported configuration field
     * @throws JsonException when the repaired value cannot be compared
     */
    public function write(string $selector, $value): void
    {
        if ($this->arrayConfiguration) {
            $this->writeArray($selector, $value);
            return;
        }
        $pointer = new Pointer();
        $field = $pointer->tokens($selector)[0];
        $method = match ($field) {
            'riskyAllowed' => 'setRiskyAllowed',
            'rules' => 'setRules',
            default => throw new PolicyException('Unknown PHP configuration field "' . $selector . '". Select /riskyAllowed or a value under /rules.'),
        };
        $data = $this->data->native();
        $updated = $pointer->write($this->containers($data, $pointer->tokens($selector)), $selector, $value);
        $argument = $pointer->read($updated, '/' . $field)->value;
        $source = (new PhpConfigEditor())->write($this->source, $method, var_export($this->literal($argument), true));
        $candidate = new self($source);
        if (!(new Constraint())->equal($candidate->read($selector)->value, $value)) {
            throw new PolicyException('PHP repair at "' . $selector . '" did not round-trip. Use a literal value that the configuration can represent.');
        }
        $this->source = $source;
        $this->data = $candidate->data;
    }

    /**
     * Sets a field in a returned PHP array and verifies the resulting literal before committing it.
     * @throws JsonException when the result cannot be compared
     * @throws PolicyException when the value cannot round-trip through PHP literals
     */
    public function writeArray(string $selector, mixed $value): void
    {
        $pointer = new Pointer();
        $data = $this->data->native();
        $updated = $pointer->write($this->containers($data, $pointer->tokens($selector)), $selector, $value);
        [$start, $end] = (new PhpConfigEditor())->returnedExpression(array_values(PhpToken::tokenize($this->source)));
        $source = substr($this->source, 0, $start) . var_export($this->literal($updated), true) . substr($this->source, $end);
        $candidate = new self($source);
        if (!(new Constraint())->equal($candidate->data->native(), $this->literal($updated))) {
            throw new PolicyException('PHP repair at "' . $selector . '" did not round-trip. Use literal configuration values.');
        }
        $this->source = $source;
        $this->data = $candidate->data;
    }

    /**
     * Interprets empty PHP arrays along the selected path as maps or lists from the next key.
     * PHP uses the same empty-array literal for both container kinds.
     * @param list<string> $tokens
     */
    public function containers(mixed $data, array $tokens): mixed
    {
        if ($tokens === []) {
            return $data;
        }
        if ($data === []) {
            $data = (new Pointer())->container($tokens);
        }
        $key = array_shift($tokens);
        if (is_array($data) && array_key_exists($key, $data)) {
            $data[$key] = $this->containers($data[$key], $tokens);
        } elseif ($data instanceof stdClass && property_exists($data, $key)) {
            $data->{$key} = $this->containers($data->{$key}, $tokens);
        }
        return $data;
    }

    /**
     * Converts mapping containers into PHP arrays while retaining scalar types.
     * @throws PolicyException when a value cannot be expressed as a PHP configuration literal
     */
    public function literal(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            $value = get_object_vars($value);
        }
        if (is_array($value)) {
            $entries = [];
            foreach ($value as $key => $entry) {
                $entries[$key] = $this->literal($entry);
            }
            return $entries;
        }
        if (is_scalar($value) || $value === null) {
            return $value;
        }
        throw new PolicyException('PHP configuration values must be scalar values, null, lists or maps. Replace the object with a literal configuration value.');
    }

    /**
     * Returns the current PHP source, preserving the original bytes until a field changes.
     */
    public function encode(): string
    {
        return $this->source;
    }
}
