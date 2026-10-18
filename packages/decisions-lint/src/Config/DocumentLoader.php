<?php

declare(strict_types=1);

namespace DecisionsLint\Config;

use DecisionsLint\Diagnostic\LintException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads one YAML document and checks it against its schema before anything else looks at it.
 */
final class DocumentLoader
{
    /**
     * Creates a loader, optionally with a shared validator.
     */
    public function __construct(private ?SchemaValidator $validator = null)
    {
    }

    /**
     * Returns the mapping a file holds once it passes the named schema.
     *
     * @return array<string, mixed>
     * @throws LintException when the file is missing, not YAML, or departs from the schema
     */
    public function load(string $path, string $schemaName, string $label): array
    {
        if (!is_file($path)) {
            $hint = $schemaName === SchemaCatalog::DECISIONS ? 'Run decisions-lint init to create it, or pass --config.' : 'Check that the path is relative to the file that imports it.';
            throw new LintException(sprintf('%s not found: %s. %s', $schemaName === SchemaCatalog::DECISIONS ? 'Configuration' : 'Rule file', $path, $hint));
        }
        try {
            $document = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new LintException(sprintf('%s is not valid YAML: %s. Correct the YAML syntax.', $label, $exception->getMessage()), 0, $exception);
        }
        ($this->validator ?? new SchemaValidator())->validate($document, $schemaName, $label);
        $mapping = [];
        foreach (is_array($document) ? $document : [] as $key => $value) {
            $mapping[(string) $key] = $value;
        }

        return $mapping;
    }
}
