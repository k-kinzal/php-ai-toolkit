<?php

declare(strict_types=1);

namespace Guard\Structure;

/**
 * The exact bytes of a file, for policies that compare whole contents.
 */
final class Text implements Subject
{
    /**
     * Keeps the bytes read by the collector.
     */
    public function __construct(private string $content)
    {
    }

    /**
     * Returns the file content without any normalization.
     */
    public function content(): string
    {
        return $this->content;
    }
}
