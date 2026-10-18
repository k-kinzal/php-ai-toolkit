<?php

declare(strict_types=1);

namespace DecisionsLint\Diagnostic;

/**
 * One rule violation at one position in one file, with the change that resolves it.
 *
 * @property-read string $path the file, relative to the configuration directory
 * @property-read int $line the 1-based line of the declaration the rule judged
 * @property-read int $column the 1-based column of that declaration
 * @property-read string $rule the identifier of the violated rule
 * @property-read string $severity error, warning or info
 * @property-read string $symbol the declaration that was judged, such as App\Analyzer::foo
 * @property-read string $message what is wrong
 * @property-read string $fix what to change so the rule passes
 * @property-read float $confidence how sure the decision model was, from 0 to 1
 */
final class Finding
{
    /**
     * Creates the immutable value.
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private int $line,
        /** @readonly */
        private int $column,
        /** @readonly */
        private string $rule,
        /** @readonly */
        private string $severity,
        /** @readonly */
        private string $symbol,
        /** @readonly */
        private string $message,
        /** @readonly */
        private string $fix,
        /** @readonly */
        private float $confidence,
    ) {
    }

    /**
     * Returns a declared immutable property.
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'path' => $this->path,
            'line' => $this->line,
            'column' => $this->column,
            'rule' => $this->rule,
            'severity' => $this->severity,
            'symbol' => $this->symbol,
            'message' => $this->message,
            'fix' => $this->fix,
            'confidence' => $this->confidence,
            default => null,
        };
    }
}
