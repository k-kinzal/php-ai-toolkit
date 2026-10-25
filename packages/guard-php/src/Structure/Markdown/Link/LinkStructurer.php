<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown\Link;

use Guard\Diagnostic\PolicyException;
use Guard\Structure\Source;
use Guard\Structure\Structurer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\MarkdownParser;

/**
 * Extracts CommonMark link destinations, excluding code and raw HTML.
 */
final class LinkStructurer implements Structurer
{
    /**
     * Parses inline, reference-style, and image links without interpreting code samples.
     * @throws PolicyException when the Markdown parser cannot build a document
     */
    public function structure(Source $source): LinkList
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new FrontMatterExtension());
        try {
            $walker = (new MarkdownParser($environment))->parse($source->text())->walker();
        } catch (CommonMarkException $error) {
            throw new PolicyException('Cannot parse Markdown links: ' . $error->getMessage() . '. Correct the Markdown document.', 0, $error);
        }
        $links = [];
        while (($event = $walker->next()) !== null) {
            $node = $event->getNode();
            if (!$event->isEntering() || (!$node instanceof Link && !$node instanceof Image)) {
                continue;
            }
            $path = $this->localPath($node->getUrl());
            if ($path === null) {
                continue;
            }
            $parent = $node->parent();
            while ($parent !== null && !$parent instanceof AbstractBlock) {
                $parent = $parent->parent();
            }
            $links[] = ['url' => $node->getUrl(), 'path' => $path, 'line' => $parent instanceof AbstractBlock ? ($parent->getStartLine() ?? 1) : 1];
        }
        return new LinkList($links);
    }

    /**
     * Leaves site-root routes, fragments, and URI schemes to their owning renderer.
     */
    public function localPath(string $url): ?string
    {
        if ($url === '' || $url[0] === '/' || preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) === 1) {
            return null;
        }
        $path = rawurldecode(preg_split('/[?#]/', $url, 2)[0] ?? '');
        return $path === '' || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/') ? null : $path;
    }
}
