<?php

declare(strict_types=1);

namespace Guard\Structure;

use Guard\Diagnostic\PolicyException;
use Guard\Structure\Document\DataDocument;
use Guard\Structure\Document\PhpDocument;
use Guard\Structure\Document\XmlDocument;
use Guard\Structure\Php\Tokens;
use JsonException;
use RuntimeException;

/**
 * Structures registered data formats using only already-read bytes.
 */
final class DocumentStructurer implements Structurer
{
    /**
     * Creates the DocumentStructurer with its declared dependencies.
     */
    public function __construct(private string $format)
    {
    }
    /** Parses a document without evaluating its field constraints.
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function structure(Source $source): ParsedDocument
    {
        if ($this->format === 'php') {
            $tokens = $source->structure('php.tokens');
            if (!$tokens instanceof Tokens) {
                throw new PolicyException('Structure php.tokens must return Tokens. Register TokenParser for that id.');
            }
            return new ParsedDocument(new PhpDocument($source->text(), $tokens->all()), $source->text());
        }
        return new ParsedDocument($this->format === 'xml' ? new XmlDocument($source->text()) : new DataDocument($this->format, $source->text()), $source->text());
    }
}
