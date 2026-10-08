<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use Guard\Diagnostic\PolicyException;
use Guard\Input\Path as PathResolver;
use Guard\Policy\Definition\DocumentConfig;

use function is_array;
use function is_string;
use function sprintf;

/**
 * Reads the "documents" mapping of doc-guard.yaml.
 */
final class DocumentListConfigReader
{
    /** @readonly */
    private DocumentConfigReader $documentReader;

    /** @readonly */
    private PathResolver $pathResolver;

    /**
     * Creates a reader from per-document reading and path normalization.
     */
    public function __construct(?DocumentConfigReader $documentReader = null, ?PathResolver $pathResolver = null)
    {
        $this->documentReader = $documentReader ?? new DocumentConfigReader();
        $this->pathResolver = $pathResolver ?? new PathResolver();
    }

    /**
     * Reads every declared document, keyed in the file by its path relative to doc-guard.yaml.
     *
     * @param mixed $value
     * @return list<DocumentConfig>
     *
     * @throws PolicyException when the section is empty, not a mapping, or declares a document twice
     */
    public function read($value): array
    {
        if (!is_array($value) || $value === []) {
            throw new PolicyException('Invalid doc-guard.yaml: "documents" must be a non-empty mapping from document paths to their declared structure.');
        }

        $documents = [];
        $seen = [];
        foreach ($value as $path => $entry) {
            if (!is_string($path) || $path === '') {
                throw new PolicyException(sprintf('Invalid doc-guard.yaml: "documents" must be keyed by document paths such as README.md, found "%s".', (string) $path));
            }

            $normalized = $this->pathResolver->spelling($path);
            if (isset($seen[$normalized])) {
                throw new PolicyException(sprintf('Invalid doc-guard.yaml: "documents" declares %s more than once.', $normalized));
            }
            $seen[$normalized] = true;
            $documents[] = $this->documentReader->read($normalized, $entry);
        }

        return $documents;
    }
}
