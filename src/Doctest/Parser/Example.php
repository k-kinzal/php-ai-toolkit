<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Parser;

use PhpAiToolkit\Doctest\Scanner\Target;

use function sprintf;

/**
 * Represents a single example extracted from a docblock.
 *
 * An Example contains the code to execute, the target it belongs to,
 * and metadata about its location and description.
 *
 * @property-read string $code
 * @property-read Target $target
 * @property-read int $lineNumber
 * @property-read int $index
 * @property-read ?string $description
 */
final class Example
{
    /**
     * @param string $code the example code to execute
     * @param Target $target the target this example belongs to
     * @param int $lineNumber line number in the source file
     * @param int $index zero-based index among examples for this target
     * @param string|null $description optional description from the at-example tag
     */
    public function __construct(
        /** @readonly */
        private string $code,
        /** @readonly */
        private Target $target,
        /** @readonly */
        private int $lineNumber,
        /** @readonly */
        private int $index,
        /** @readonly */
        private ?string $description = null,
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
            'code' => $this->code,
            'target' => $this->target,
            'lineNumber' => $this->lineNumber,
            'index' => $this->index,
            'description' => $this->description,
            default => null,
        };
    }

    /**
     * Returns a human-readable name for the example.
     *
     * Combines the target's short name with the example index and optional description.
     */
    public function getName(): string
    {
        $name = $this->target->getShortName();
        $desc = $this->description !== null ? ': ' . $this->description : '';

        return sprintf('%s example #%d%s', $name, $this->index + 1, $desc);
    }

    /**
     * Returns the PHPUnit test name for this example.
     *
     * Includes the example name and line number for identification.
     */
    public function getTestName(): string
    {
        return sprintf('Doctest: %s (line %d)', $this->getName(), $this->lineNumber);
    }
}
