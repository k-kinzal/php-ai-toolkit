<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExampleOnPublicApi;

/**
 * Declares itself public API and documents a runnable example.
 *
 * @visibility public
 *
 * @example Building the type
 *     $value = new DocumentedExamples();
 *     $value->label() // => 'documented'
 */
final class DocumentedExamples
{
    /**
     * Returns the label.
     *
     * @visibility public
     *
     * ```php
     * (new DocumentedExamples())->label() // => 'documented'
     * ```
     */
    public function label(): string
    {
        return 'documented';
    }
}
