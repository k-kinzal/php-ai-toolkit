<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;

/**
 * Detects whether a PHPDoc block documents an example doctest can run.
 *
 * Extraction is delegated to the doctest grammar rather than repeated here, so
 * the rule requires exactly what "vendor/bin/doctest" would execute: a block
 * this rule accepts is a block the runner picks up.
 */
final class RunnableExampleDetector
{
    /** @readonly */
    private DoctestExtractor $extractor;

    /**
     * Creates a detector from the doctest example grammar.
     */
    public function __construct(?DoctestExtractor $extractor = null)
    {
        $this->extractor = $extractor ?? new DoctestExtractor();
    }

    /**
     * Reports whether the PHPDoc block carries at least one runnable example.
     *
     * A single-line at-example tag documents a shape rather than a program, so
     * it is rendered and never run, and it does not satisfy the requirement.
     */
    public function hasRunnableExample(?string $docComment): bool
    {
        if ($docComment === null) {
            return false;
        }

        foreach ($this->extractor->extract($docComment) as $example) {
            if ($example->source !== 'tag-inline') {
                return true;
            }
        }

        return false;
    }
}
