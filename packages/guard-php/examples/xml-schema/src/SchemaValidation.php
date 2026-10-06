<?php

declare(strict_types=1);

namespace Example\Guard;

use Guard\Document\XmlDocument;
use Guard\Policy\PolicyException;

/** Validates against a self-contained XSD without libxml loading undeclared dependencies. */
final class SchemaValidation
{
    private string $source;

    public function __construct(XmlDocument $schema, private string $path)
    {
        foreach (['include', 'import', 'redefine'] as $name) {
            if ($schema->dom()->getElementsByTagNameNS('http://www.w3.org/2001/XMLSchema', $name)->length > 0) {
                throw new PolicyException('Schema "' . $path . '" uses xs:' . $name . '. Supply a self-contained XSD; this example does not load schema dependencies.');
            }
        }
        $this->source = $schema->encode();
    }

    /** @return list<string> */
    public function errors(XmlDocument $document): array
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        set_error_handler(function (int $severity, string $message): void {
            throw new PolicyException('Invalid schema "' . $this->path . '": ' . $message . '. Supply a valid XSD 1.0 schema.');
        });
        try {
            if ($document->dom()->schemaValidateSource($this->source)) {
                return [];
            }
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $errors[] = 'line ' . $error->line . ': ' . trim($error->message);
            }
            return $errors === [] ? ['Schema validation failed.'] : $errors;
        } finally {
            restore_error_handler();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
