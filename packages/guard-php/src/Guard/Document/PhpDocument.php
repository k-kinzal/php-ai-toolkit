<?php

declare(strict_types=1);

namespace Toolkit\Guard\Document;

use JsonException;
use Toolkit\Guard\Policy\PolicyException;

/**
 * Reads literal values from a PHP configuration file. The file is not executed or rewritten.
 */
final class PhpDocument
{
    /**
     * Keeps the original PHP source. Literal calls are parsed when a field is read.
     */
    public function __construct(private string $source)
    {
    }

    /**
     * Reads a field from the literal risky flag or rule map.
     *
     * @throws JsonException when the selected value cannot be compared
     */
    public function read(string $selector): Selection
    {
        return (new Pointer())->read((new PhpConfigReader())->read($this->source), $selector);
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
