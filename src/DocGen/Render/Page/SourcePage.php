<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function explode;

use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;

/**
 * Renders one highlighted source file with linkable line anchors.
 */
final class SourcePage
{
    /** @readonly */
    private PageChrome $chrome;

    /** @readonly */
    private BreadcrumbHtml $breadcrumb;

    /** @readonly */
    private SidebarHtml $sidebar;

    /**
     * Creates a source page renderer from its collaborators.
     */
    public function __construct(?PageChrome $chrome = null, ?BreadcrumbHtml $breadcrumb = null, ?SidebarHtml $sidebar = null)
    {
        $this->chrome = $chrome ?? new PageChrome();
        $this->breadcrumb = $breadcrumb ?? new BreadcrumbHtml();
        $this->sidebar = $sidebar ?? new SidebarHtml();
    }

    /**
     * Renders one complete source page document.
     */
    public function render(RenderKit $services, string $relativeFile, string $code): string
    {
        $pagePath = $services->url->sourcePage($relativeFile);
        $crumbs = [
            ['label' => 'src', 'path' => 'index.html'],
            ['label' => $relativeFile, 'path' => null],
        ];

        return $this->chrome->page(
            $services,
            $pagePath,
            $relativeFile,
            $this->breadcrumb->build($services, $pagePath, $crumbs),
            $this->sidebar->build($services, $pagePath, new SidebarScope(null, null, null, [])),
            $this->content($services, $relativeFile, $code),
        );
    }

    /**
     * Renders the numbered source listing.
     */
    public function content(RenderKit $services, string $relativeFile, string $code): string
    {
        $html = sprintf('<div class="symbol-head"><h1 class="source-title">%s</h1></div>', $services->escaper->e($relativeFile)) . "\n";
        $html .= '<pre class="source"><code>';
        foreach (explode("\n", $services->highlighter->highlight($code)) as $index => $line) {
            $number = $index + 1;
            $html .= sprintf('<span class="src-line" id="L%d"><a class="ln" href="#L%d">%d</a>%s</span>' . "\n", $number, $number, $number, $line);
        }

        return $html . '</code></pre>' . "\n";
    }
}
