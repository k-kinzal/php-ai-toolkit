<?php

declare(strict_types=1);

namespace Guard\Document;

use Guard\Policy\PolicyException;
use JsonException;

/**
 * Reads literal values from a PHP configuration file. The file is not executed or rewritten.
 */
final class PhpDocument
{
    private DocumentNode $data;
    /**
     * Keeps the original PHP source. Literal calls are structured during collection.
     */
    public function __construct(private string $source)
    {
        $this->data = new DocumentNode((new PhpConfigReader())->read($source));
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
     * Refuses to rewrite PHP configuration.
     *
     * @param mixed $value
     * @throws PolicyException always, because a literal PHP file cannot be repaired safely
     */
    public function write(string $selector, $value): void
    {
        throw new PolicyException('PHP configuration cannot be repaired. Edit the literal setRiskyAllowed and setRules calls so they match the toolkit rules.');
    }

    /**
     * Returns the original PHP source.
     */
    public function encode(): string
    {
        return $this->source;
    }
}
