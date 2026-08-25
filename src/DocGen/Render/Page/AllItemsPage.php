<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function count;

use PhpAiToolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;

/**
 * Renders the complete item listing of one package.
 *
 * The listing is the drill-down target of the package page and the fallback
 * route when a symbol is known by kind rather than by namespace.
 */
final class AllItemsPage
{
    /** @readonly */
    private PageChrome $chrome;

    /** @readonly */
    private SidebarHtml $sidebar;

    /** @readonly */
    private BreadcrumbHtml $breadcrumb;

    /** @readonly */
    private SymbolIndex $symbols;

    /** @readonly */
    private SymbolListHtml $symbolList;

    /**
     * Creates an all-items page renderer from its collaborators.
     */
    public function __construct(
        ?PageChrome $chrome = null,
        ?SidebarHtml $sidebar = null,
        ?BreadcrumbHtml $breadcrumb = null,
        ?SymbolIndex $symbols = null,
        ?SymbolListHtml $symbolList = null,
    ) {
        $this->chrome = $chrome ?? new PageChrome();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->breadcrumb = $breadcrumb ?? new BreadcrumbHtml();
        $this->symbols = $symbols ?? new SymbolIndex();
        $this->symbolList = $symbolList ?? new SymbolListHtml();
    }

    /**
     * Renders one complete all-items page document.
     */
    public function render(RenderKit $services, string $packageName): string
    {
        $pagePath = $services->url->allItemsPage($packageName);
        $rows = $this->symbols->inPackage($services, $packageName);
        $crumbs = [
            ['label' => $packageName, 'path' => $services->url->packagePage($packageName)],
            ['label' => 'All items', 'path' => null],
        ];

        return $this->chrome->page(
            $services,
            $pagePath,
            'All items',
            sprintf('Every documented item of the %s package.', $packageName),
            $this->breadcrumb->build($services, $pagePath, $crumbs),
            $this->sidebar->build($services, $pagePath, new SidebarScope($packageName, null, null, $this->symbolList->sections($rows))),
            $this->content($services, $pagePath, $rows),
        );
    }

    /**
     * Renders the listing content of one package.
     *
     * @param list<SymbolRow> $rows
     */
    public function content(RenderKit $services, string $pagePath, array $rows): string
    {
        $html = sprintf(
            '<div class="symbol-head"><h1>All items <span class="count">%d</span></h1></div>',
            count($rows),
        ) . "\n";

        return $html . $this->symbolList->groups($services, $pagePath, $rows, true);
    }
}
