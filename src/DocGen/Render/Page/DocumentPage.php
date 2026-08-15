<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function dirname;

use PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;

/**
 * Renders one Markdown document of a package as a site page.
 *
 * Prose written next to the code answers the questions the API listing
 * cannot, so the documents of a repository are part of the site and link to
 * each other exactly as they do in the repository.
 */
final class DocumentPage
{
    /** @readonly */
    private PageChrome $chrome;

    /** @readonly */
    private SidebarHtml $sidebar;

    /** @readonly */
    private BreadcrumbHtml $breadcrumb;

    /** @readonly */
    private DocumentListHtml $documents;

    /**
     * Creates a document page renderer from its collaborators.
     */
    public function __construct(
        ?PageChrome $chrome = null,
        ?SidebarHtml $sidebar = null,
        ?BreadcrumbHtml $breadcrumb = null,
        ?DocumentListHtml $documents = null,
    ) {
        $this->chrome = $chrome ?? new PageChrome();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->breadcrumb = $breadcrumb ?? new BreadcrumbHtml();
        $this->documents = $documents ?? new DocumentListHtml();
    }

    /**
     * Renders one complete document page.
     */
    public function render(RenderKit $services, MarkdownDoc $document, string $markdown): string
    {
        $pagePath = $services->url->documentPage($document->packageName, $document->path);
        $crumbs = [
            ['label' => $document->packageName, 'path' => $services->url->packagePage($document->packageName)],
            ['label' => $document->path, 'path' => null],
        ];

        return $this->chrome->page(
            $services,
            $pagePath,
            $document->title,
            $this->breadcrumb->build($services, $pagePath, $crumbs),
            $this->sidebar->build($services, $pagePath, new SidebarScope($document->packageName, null, null, [])),
            $this->content($services, $pagePath, $document, $markdown),
        );
    }

    /**
     * Renders the heading and the document body.
     */
    public function content(RenderKit $services, string $pagePath, MarkdownDoc $document, string $markdown): string
    {
        $html = sprintf(
            '<div class="symbol-head"><h1><span class="chip chip-kind k-document">document</span>%s</h1>'
            . '<div class="symbol-meta"><span class="src-link">%s</span></div></div>',
            $services->escaper->e($document->title),
            $services->escaper->e($document->path),
        ) . "\n";

        return $html . '<section class="readme">' . $this->body($services, $pagePath, $document, $markdown) . '</section>' . "\n";
    }

    /**
     * Renders the Markdown body with resolved links and highlighted PHP.
     */
    public function body(RenderKit $services, string $pagePath, MarkdownDoc $document, string $markdown): string
    {
        $directory = dirname($document->path);
        $links = $this->documents->links($services, $pagePath, $document->packageName, $directory === '.' ? '' : $directory);

        return $services->markdown->withLinks($links)->render($markdown, static function (string $code, string $language) use ($services): ?string {
            if ($language === 'php') {
                return '<pre class="code-block"><code>' . $services->highlighter->highlightSnippet($code) . '</code></pre>' . "\n";
            }

            return null;
        });
    }
}
