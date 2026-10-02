<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Generation;

use function sprintf;

use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;
use Toolkit\DocGuard\Filesystem\MarkdownFileReader;
use Toolkit\DocGuard\Markdown\HeadingParser;

/**
 * Generates a doc-guard.yaml that declares the current heading structure of documents.
 */
final class ConfigGenerator
{
    /** @readonly */
    private GenerationTargetCollector $targetCollector;

    /** @readonly */
    private DocGuardPathResolver $pathResolver;

    /** @readonly */
    private MarkdownFileReader $reader;

    /** @readonly */
    private HeadingParser $parser;

    /** @readonly */
    private ConfigYamlWriter $writer;

    /**
     * Creates a generator from target selection, parsing, and YAML writing.
     */
    public function __construct(
        ?GenerationTargetCollector $targetCollector = null,
        ?DocGuardPathResolver $pathResolver = null,
        ?MarkdownFileReader $reader = null,
        ?HeadingParser $parser = null,
        ?ConfigYamlWriter $writer = null,
    ) {
        $this->targetCollector = $targetCollector ?? new GenerationTargetCollector();
        $this->pathResolver = $pathResolver ?? new DocGuardPathResolver();
        $this->reader = $reader ?? new MarkdownFileReader();
        $this->parser = $parser ?? new HeadingParser();
        $this->writer = $writer ?? new ConfigYamlWriter();
    }

    /**
     * Returns the YAML declaring the documents selected from the directory and paths.
     *
     * @param list<string> $paths
     *
     * @throws DocGuardException when a path does not exist, no document is found, or a document cannot be read
     */
    public function generate(string $directory, array $paths): string
    {
        $targets = $this->targetCollector->collect($directory, $paths);
        if ($targets['documents'] === []) {
            throw new DocGuardException(sprintf(
                'No Markdown documents found in %s. Pass the documents or directories to declare, for example: doc-guard --generate README.md docs',
                $directory,
            ));
        }

        $documents = [];
        foreach ($targets['documents'] as $path) {
            $documents[$path] = $this->parser->parse($this->reader->read($this->pathResolver->resolve($directory, $path)));
        }

        return $this->writer->write($documents, $targets['scan']);
    }
}
