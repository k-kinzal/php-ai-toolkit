<?php

declare(strict_types=1);

namespace Guard\Structure;

use Guard\Document\DataDocument;
use Guard\Document\PhpDocument;
use Guard\Document\XmlDocument;

/**
 * A parsed configuration document shared by policies as an immutable input.
 */
final class ParsedDocument implements Subject
{
    /**
     * Creates the DocumentValue with its declared dependencies.
     */
    public function __construct(private DataDocument|PhpDocument|XmlDocument $document, private string $original)
    {
    }
    /**
     * Returns a private editable copy; other policies retain the original snapshot.
     */
    public function copy(): DataDocument|PhpDocument|XmlDocument
    {
        return clone $this->document;
    }
    /**
     * Returns the original bytes for concurrent-change checks and repairs.
     */
    public function original(): string
    {
        return $this->original;
    }
}
