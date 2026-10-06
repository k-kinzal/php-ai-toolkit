<?php

declare(strict_types=1);

namespace Guard\Document;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use DOMXPath;
use Guard\Policy\PolicyException;
use JsonException;

/**
 * Edits unique XML attributes and leaf elements with XPath selectors.
 */
final class XmlDocument
{
    private DOMDocument $document;
    /**
     * @throws PolicyException when XML is invalid or uses a DTD
     */
    public function __construct(string $source)
    {
        if (trim($source) === '') {
            throw new PolicyException('XML document is empty. Add a root element before running guard.');
        }
        if (stripos($source, '<!DOCTYPE') !== false || stripos($source, '<!ENTITY') !== false) {
            throw new PolicyException('XML DTDs and entity declarations are unsupported. Remove them before checking configuration.');
        }
        $this->document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $valid = $this->document->loadXML($source, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (!$valid) {
            throw new PolicyException('Invalid XML. Correct the syntax before running guard.');
        }
    }
    /**
     * Keeps each policy evaluation isolated from the collected XML snapshot.
     */
    public function __clone()
    {
        $this->document = clone $this->document;
    }
    /**
     * Exposes the parsed DOM for XML policies, including XSD validation, without reparsing.
     * Obtain this document through ParsedDocument::copy() to isolate policy mutations.
     */
    public function dom(): DOMDocument
    {
        return $this->document;
    }
    /**
     * @throws PolicyException when a selector is invalid or ambiguous
     */
    public function node(string $selector): ?DOMNode
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $nodes = (new DOMXPath($this->document))->query($selector);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if ($nodes === false || $nodes->length > 1) {
            throw new PolicyException('XML selector "' . $selector . '" must be valid XPath selecting at most one node.');
        }
        $node = $nodes->item(0);
        return $node instanceof DOMNode ? $node : null;
    }
    /**
     * Reads XML values as strings, matching the XML data model.
     * @throws JsonException
     */
    public function read(string $selector): Selection
    {
        $node = $this->node($selector);
        return new Selection($node !== null, $node === null ? null : $node->textContent);
    }
    /**
     * @param mixed $value
     * @throws PolicyException when a selector cannot be repaired without destroying child content
     */
    public function write(string $selector, $value): void
    {
        if (!is_string($value)) {
            throw new PolicyException('XML repair values must be strings. Quote the value in guard.yaml.');
        }
        $node = $this->node($selector);
        if ($node === null && preg_match('~^(.*)/@([A-Za-z_][A-Za-z0-9_.-]*)$~', $selector, $match) === 1) {
            $parent = $this->node($match[1]);
            if ($parent instanceof DOMElement) {
                try {
                    $parent->setAttribute($match[2], $value);
                } catch (DOMException $exception) {
                    throw new PolicyException('Cannot set XML attribute "' . $match[2] . '". ' . $exception->getMessage(), 0, $exception);
                }

                return;
            }
        }
        if ($node instanceof DOMAttr) {
            $node->value = $value;
            return;
        }
        if ($node instanceof DOMElement && $node->getElementsByTagName('*')->length === 0) {
            $node->textContent = $value;
            return;
        }
        throw new PolicyException('XML selector "' . $selector . '" must identify an attribute or an existing leaf element. Create its parent structure first.');
    }
    /**
     * @throws PolicyException when XML cannot be serialized
     */
    public function encode(): string
    {
        try {
            $source = $this->document->saveXML();
        } catch (DOMException $exception) {
            throw new PolicyException('Cannot serialize XML. No changes were written.', 0, $exception);
        }
        if ($source === false) {
            throw new PolicyException('Cannot serialize XML. No changes were written.');
        }
        return $source;
    }
}
