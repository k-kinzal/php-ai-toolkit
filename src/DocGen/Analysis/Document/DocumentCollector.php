<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Document;

use function explode;
use function file_get_contents;
use function is_file;

use PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Filesystem\MarkdownFileFinder;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;

use function preg_match;

/**
 * Collects the Markdown documents that belong to the analyzed repository.
 *
 * Only packages of the repository are scanned: a dependency ships the prose
 * of another project, which does not describe this one.
 */
final class DocumentCollector
{
    /** @readonly */
    private MarkdownFileFinder $finder;

    /** @readonly */
    private DocGenPathResolver $pathResolver;

    /**
     * Creates a document collector from its filesystem collaborators.
     */
    public function __construct(?MarkdownFileFinder $finder = null, ?DocGenPathResolver $pathResolver = null)
    {
        $this->finder = $finder ?? new MarkdownFileFinder();
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
    }

    /**
     * Collects the Markdown documents of every documented package.
     *
     * @param list<DiscoveredPackage> $packages
     *
     * @return list<MarkdownDoc>
     */
    public function collect(DocGenConfig $config, array $packages): array
    {
        $documents = [];
        $seen = [];
        foreach ($packages as $package) {
            if ($package->isVendor) {
                continue;
            }

            foreach ($this->finder->find($package->manifest->directory, $config->root, $config->exclude) as $file) {
                if (isset($seen[$file])) {
                    continue;
                }

                $seen[$file] = true;
                $path = $this->pathResolver->relative($package->manifest->directory, $file);
                $documents[] = new MarkdownDoc(
                    $package->manifest->name,
                    $path,
                    $this->pathResolver->relative($config->root, $file),
                    $this->title($file, $path),
                );
            }
        }

        return $documents;
    }

    /**
     * Reads the title of one document from its first heading.
     *
     * A document without a level one heading is titled by its path, which is
     * how a reader refers to it in the repository anyway. Headings inside a
     * code fence are shell comments, not titles, so fences are skipped.
     */
    public function title(string $file, string $path): string
    {
        $contents = is_file($file) ? file_get_contents($file) : false;
        $fenced = false;
        foreach ($contents === false ? [] : explode("\n", $contents) as $line) {
            if (preg_match('/^\s*```/', $line) === 1) {
                $fenced = !$fenced;
                continue;
            }

            if (!$fenced && preg_match('/^#\s+(.+?)\s*$/', $line, $match) === 1) {
                return $match[1];
            }
        }

        return $path;
    }
}
