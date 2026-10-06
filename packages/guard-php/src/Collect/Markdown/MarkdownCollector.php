<?php

declare(strict_types=1);

namespace Guard\Collect\Markdown;

use Guard\Collect\Collector;
use Guard\Collect\Markdown\Filesystem\MarkdownFileFinder;
use Guard\Collect\Markdown\Filesystem\MarkdownFileReader;
use Guard\Collect\Markdown\Filesystem\PathResolver;
use Guard\Collect\Markdown\Parsing\HeadingParser;
use Guard\Execution\Context;

/**
 * Parses headings and discovers files before any documentation constraints run.
 */
final class MarkdownCollector implements Collector
{
    /**
     * @return list<MarkdownDocuments>
     */
    public function collect(Context $context): array
    {
        $config = $context->configuration->documentation;
        if ($config === null) {
            return [];
        }
        $headings = [];
        foreach ($config->documents as $document) {
            $path = (new PathResolver())->resolve($config->root, $document->path);
            $headings[$document->path] = is_file($path)
                ? (new HeadingParser())->parse((new MarkdownFileReader())->read($path)) : null;
        }
        $finder = new MarkdownFileFinder();
        $excluded = $finder->find($config->root, $config->exclude);
        $discovered = [];
        foreach ($config->scan as $pattern) {
            $discovered[] = ['pattern' => $pattern, 'paths' => $finder->find($config->root, [$pattern])];
        }
        return [new MarkdownDocuments($headings, $excluded, $discovered)];
    }
}
