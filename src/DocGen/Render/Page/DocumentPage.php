<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use Closure;

use function dirname;

use PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc;
use PhpAiToolkit\DocGen\Render\Diff\DiffBanner;
use PhpAiToolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\DocumentListHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SidebarHtml;
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

    /** @readonly */
    private MarkdownDiffHtml $diffHtml;

    /** @readonly */
    private DiffBanner $banner;

    /**
     * Creates a document page renderer from its collaborators.
     */
    public function __construct(
        ?PageChrome $chrome = null,
        ?SidebarHtml $sidebar = null,
        ?BreadcrumbHtml $breadcrumb = null,
        ?DocumentListHtml $documents = null,
        ?MarkdownDiffHtml $diffHtml = null,
        ?DiffBanner $banner = null,
    ) {
        $this->chrome = $chrome ?? new PageChrome();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->breadcrumb = $breadcrumb ?? new BreadcrumbHtml();
        $this->documents = $documents ?? new DocumentListHtml();
        $this->diffHtml = $diffHtml ?? new MarkdownDiffHtml();
        $this->banner = $banner ?? new DiffBanner();
    }

    /**
     * Renders one complete document page.
     *
     * @param ?string $markdown the document as the head revision has it
     * @param ?string $baseMarkdown the document as the base revision had it
     */
    public function render(RenderKit $services, MarkdownDoc $document, ?string $markdown, ?string $baseMarkdown = null): string
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
            sprintf('%s, a document of the %s package.', $document->title, $document->packageName),
            $this->breadcrumb->build($services, $pagePath, $crumbs),
            $this->sidebar->build($services, $pagePath, new SidebarScope($document->packageName, null, null, [])),
            $this->content($services, $pagePath, $document, $markdown, $baseMarkdown),
        );
    }

    /**
     * Renders the heading and the document body.
     *
     * @param ?string $markdown the document as the head revision has it
     * @param ?string $baseMarkdown the document as the base revision had it
     */
    public function content(RenderKit $services, string $pagePath, MarkdownDoc $document, ?string $markdown, ?string $baseMarkdown = null): string
    {
        $status = $services->diff->documentStatus($document->packageName, $document->path);
        $html = sprintf(
            '<div class="symbol-head"><h1><span class="chip chip-kind k-document">document</span>%s</h1>'
            . '<div class="symbol-meta"><span class="src-link">%s</span></div></div>',
            $services->escaper->e($document->title),
            $services->escaper->e($document->path),
        ) . "\n";
        $html .= $this->banner->render($services, $status);

        return $html . '<section class="readme"' . $services->diff->mark($status) . '>'
            . $this->body($services, $pagePath, $document, $markdown, $baseMarkdown) . '</section>' . "\n";
    }

    /**
     * Renders the Markdown body with resolved links and highlighted PHP.
     *
     * In a comparison the body is rendered block by block so a paragraph,
     * a list, or an example that changed can be marked on its own.
     *
     * @param ?string $markdown the document as the head revision has it
     * @param ?string $baseMarkdown the document as the base revision had it
     */
    public function body(RenderKit $services, string $pagePath, MarkdownDoc $document, ?string $markdown, ?string $baseMarkdown = null): string
    {
        $directory = dirname($document->path);
        $links = $this->documents->links($services, $pagePath, $document->packageName, $directory === '.' ? '' : $directory);
        $renderer = $services->markdown->withLinks($links);
        $fence = $this->fence($services);
        if ($services->diff->isActive()) {
            return $this->diffHtml->render($services, $renderer, $baseMarkdown, $markdown, $fence);
        }

        return $markdown === null ? '' : $renderer->render($markdown, $fence);
    }

    /**
     * Builds the fenced code renderer of a document body.
     *
     * @return Closure(string, string): ?string
     */
    public function fence(RenderKit $services): Closure
    {
        return static function (string $code, string $language) use ($services): ?string {
            if ($language === 'php') {
                return '<pre class="code-block"><code>' . $services->highlighter->highlightSnippet($code) . '</code></pre>' . "\n";
            }

            return null;
        };
    }
}
