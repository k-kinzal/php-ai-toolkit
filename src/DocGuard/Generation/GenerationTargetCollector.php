<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Generation;

use function array_merge;
use function array_unique;
use function array_values;
use function is_dir;
use function is_file;
use function sort;
use function sprintf;

use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;
use Toolkit\DocGuard\Filesystem\MarkdownFileFinder;

/**
 * Selects the documents and scan patterns of a generated doc-guard.yaml.
 */
final class GenerationTargetCollector
{
    /** @readonly */
    private DocGuardPathResolver $pathResolver;

    /** @readonly */
    private MarkdownFileFinder $finder;

    /**
     * Creates a collector from path resolution and file discovery.
     */
    public function __construct(?DocGuardPathResolver $pathResolver = null, ?MarkdownFileFinder $finder = null)
    {
        $this->pathResolver = $pathResolver ?? new DocGuardPathResolver();
        $this->finder = $finder ?? new MarkdownFileFinder();
    }

    /**
     * Returns the documents to declare and the patterns to scan, relative to the directory.
     *
     * Without paths, the Markdown files directly in the directory and every
     * Markdown file below its docs/ directory are declared and scanned. A file
     * path declares that file; a directory path declares and scans every
     * Markdown file below it.
     *
     * @param list<string> $paths
     * @return array{documents: list<string>, scan: list<string>}
     *
     * @throws DocGuardException when a path does not exist
     */
    public function collect(string $directory, array $paths): array
    {
        if ($paths === []) {
            $scan = is_dir($directory . '/docs') ? ['*.md', 'docs/**/*.md'] : ['*.md'];

            return ['documents' => $this->finder->find($directory, $scan), 'scan' => $scan];
        }

        $documents = [];
        $scan = [];
        foreach ($paths as $path) {
            $normalized = $this->pathResolver->normalize($path);
            $absolute = $this->pathResolver->resolve($directory, $normalized);
            if (is_dir($absolute)) {
                $pattern = $normalized === '.' ? '**/*.md' : $normalized . '/**/*.md';
                $scan[] = $pattern;
                $documents = array_merge($documents, $this->finder->find($directory, [$pattern]));
            } elseif (is_file($absolute)) {
                $documents[] = $normalized;
            } else {
                throw new DocGuardException(sprintf('Cannot generate a DocGuard config for %s: no such file or directory.', $path));
            }
        }

        $documents = array_values(array_unique($documents));
        sort($documents);

        return ['documents' => $documents, 'scan' => array_values(array_unique($scan))];
    }
}
