<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Analysis;

use function sprintf;

/**
 * One example extracted from a docblock, located in its source file.
 *
 * The identifier is what makes a single example runnable on its own: it names
 * the documented symbol and the position of the example within that symbol's
 * docblock, so it survives edits elsewhere in the file.
 *
 * @property-read Target $target
 * @property-read DocExample $example
 * @property-read int $line
 *
 * @visibility public
 *
 * @example Addressing one example of a method
 *     $target = new Target(Target::METHOD, 'src/Ledger.php', '', 'append', 12, 'App', 'Ledger');
 *     $example = new Example($target, new DocExample(null, 'append()', 'tag', 0), 14);
 *     $example->name() // => 'Ledger::append() example #1'
 *     $example->runnable() // => true
 */
final class Example
{
    /**
     * @param Target $target the declaration the example documents
     * @param DocExample $example the extracted example body
     * @param int $line the line the example starts on in the source file
     */
    public function __construct(
        /** @readonly */
        private Target $target,
        /** @readonly */
        private DocExample $example,
        /** @readonly */
        private int $line,
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
            'target' => $this->target,
            'example' => $this->example,
            'line' => $this->line,
            default => null,
        };
    }

    /**
     * Returns the stable identifier a single example is addressed by.
     *
     * @example Identifying the second example of a method
     *     $target = new Target(Target::METHOD, 'src/Calculator.php', '', 'add', 10, 'App', 'Calculator');
     *     $example = new Example($target, new DocExample(null, 'add(1, 2)', 'tag', 1), 12);
     *     $example->id() // => 'App\\Calculator::add()#2'
     */
    public function id(): string
    {
        return sprintf('%s#%d', $this->target->symbol(), $this->example->index + 1);
    }

    /**
     * Returns the human-readable name of the example.
     */
    public function name(): string
    {
        $description = $this->example->description;
        $suffix = $description !== null && $description !== '' ? ': ' . $description : '';

        return sprintf('%s example #%d%s', $this->target->shortName(), $this->example->index + 1, $suffix);
    }

    /**
     * Returns the code the example executes.
     */
    public function code(): string
    {
        return $this->example->code;
    }

    /**
     * Reports whether the example is executable rather than display-only.
     *
     * A single-line at-example tag documents a shape rather than a program, so
     * doctest renders it and does not run it.
     */
    public function runnable(): bool
    {
        return $this->example->source !== 'tag-inline';
    }
}
