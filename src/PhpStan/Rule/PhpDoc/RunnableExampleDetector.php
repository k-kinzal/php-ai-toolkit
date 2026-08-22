<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\Doctest\Parser\ExampleExtractor;
use PhpAiToolkit\Doctest\Scanner\Target;
use PhpAiToolkit\Doctest\Scanner\TargetKind;

/**
 * Detects whether a PHPDoc block documents an example doctest can run.
 *
 * Extraction is delegated to the doctest grammar rather than repeated here, so
 * the rule requires exactly what the doctest test suite would execute: a block
 * this rule accepts is a block that becomes a PHPUnit test case.
 */
final class RunnableExampleDetector
{
    /** @readonly */
    private ExampleExtractor $extractor;

    /**
     * Creates a detector from the doctest example grammar.
     */
    public function __construct(?ExampleExtractor $extractor = null)
    {
        $this->extractor = $extractor ?? new ExampleExtractor();
    }

    /**
     * Reports whether the PHPDoc block carries at least one runnable example.
     *
     * A single-line at-example tag carries a description and no code, so the
     * extractor yields nothing for it and it does not satisfy the requirement.
     */
    public function hasRunnableExample(?string $docComment): bool
    {
        if ($docComment === null) {
            return false;
        }

        $target = new Target(TargetKind::CLASS_LIKE, '', $docComment, '', 0);
        foreach ($this->extractor->extract($target) as $example) {
            return true;
        }

        return false;
    }
}
