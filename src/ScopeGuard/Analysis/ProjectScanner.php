<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis;

use function array_merge;
use function count;

use Toolkit\ScopeGuard\Analysis\Declaration\DeclarationCollector;
use Toolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex;
use Toolkit\ScopeGuard\Analysis\Parse\FileNamespaces;
use Toolkit\ScopeGuard\Analysis\Parse\SourceFileParser;
use Toolkit\ScopeGuard\Analysis\Reference\ReferenceCollector;
use Toolkit\ScopeGuard\Config\ScopeGuardConfig;
use Toolkit\ScopeGuard\Filesystem\PhpFileFinder;
use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * Reads every configured source file into declarations and references.
 */
final class ProjectScanner
{
    /** @readonly */
    private PhpFileFinder $fileFinder;

    /** @readonly */
    private SourceFileParser $parser;

    /** @readonly */
    private FileNamespaces $fileNamespaces;

    /** @readonly */
    private DeclarationCollector $declarationCollector;

    /** @readonly */
    private ReferenceCollector $referenceCollector;

    /**
     * Creates the scanner from file discovery, parsing, and the two collectors.
     */
    public function __construct(
        ?PhpFileFinder $fileFinder = null,
        ?SourceFileParser $parser = null,
        ?FileNamespaces $fileNamespaces = null,
        ?DeclarationCollector $declarationCollector = null,
        ?ReferenceCollector $referenceCollector = null,
    ) {
        $this->fileFinder = $fileFinder ?? new PhpFileFinder();
        $this->parser = $parser ?? new SourceFileParser();
        $this->fileNamespaces = $fileNamespaces ?? new FileNamespaces();
        $this->declarationCollector = $declarationCollector ?? new DeclarationCollector();
        $this->referenceCollector = $referenceCollector ?? new ReferenceCollector();
    }

    /**
     * Scans the configured sources once.
     *
     * @throws ScopeGuardException when a configured path is missing or a source file cannot be parsed
     */
    public function scan(ScopeGuardConfig $config): ProjectScan
    {
        $files = $this->fileFinder->find($config);
        $index = new DeclarationIndex();
        $references = [];

        foreach ($files as $absolutePath => $relativePath) {
            foreach ($this->fileNamespaces->groups($this->parser->parse($absolutePath)) as $namespace => $nodes) {
                $this->declarationCollector->collect($nodes, $relativePath, $index);
                $references = array_merge($references, $this->referenceCollector->collect($nodes, $namespace, $relativePath));
            }
        }

        return new ProjectScan($index, $references, count($files));
    }
}
