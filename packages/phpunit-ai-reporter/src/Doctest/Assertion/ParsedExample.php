<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Assertion;

use Toolkit\Doctest\Parser\Example;

/**
 * Represents an example that has been parsed into individual statements.
 *
 * A ParsedExample contains the original Example along with a list of
 * Statement objects extracted from the example code.
 *
 * @property-read Example $example
 * @property-read list<Statement> $statements
 */
final class ParsedExample
{
    /**
     * @param Example $example the original example
     * @param list<Statement> $statements the parsed statements
     */
    public function __construct(
        /** @readonly */
        private Example $example,
        /** @readonly */
        private array $statements,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'example' => $this->example,
            'statements' => $this->statements,
            default => null,
        };
    }
}
