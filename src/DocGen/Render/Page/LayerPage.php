<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function array_unshift;
use function count;
use function implode;

use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;

/**
 * Renders the symbols of one architecture layer.
 *
 * Layers come from the project's deptrac configuration, so the page also
 * states which layers this one may depend on. The namespaces the layer
 * spans are listed before its symbols: a layer is usually recognised by
 * the namespaces it owns rather than by the individual classes in it.
 */
final class LayerPage
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
     * Creates a layer page renderer from its collaborators.
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
     * Renders one complete layer page document.
     */
    public function render(RenderKit $services, string $packageName, string $layer): string
    {
        $pagePath = $services->url->layerPage($packageName, $layer);
        $rows = $this->symbols->inLayer($services, $packageName, $layer);
        $sections = $this->symbolList->sections($rows);
        if ($rows !== []) {
            array_unshift($sections, ['id' => 'namespaces', 'label' => 'Namespaces']);
        }

        $crumbs = [
            ['label' => $packageName, 'path' => $services->url->packagePage($packageName)],
            ['label' => 'Layer ' . $layer, 'path' => null],
        ];

        return $this->chrome->page(
            $services,
            $pagePath,
            'Layer ' . $layer,
            sprintf('The %s architecture layer of the %s package.', $layer, $packageName),
            $this->breadcrumb->build($services, $pagePath, $crumbs),
            $this->sidebar->build($services, $pagePath, new SidebarScope($packageName, null, null, $sections)),
            $this->content($services, $pagePath, $layer, $rows),
        );
    }

    /**
     * Renders the layer content.
     *
     * @param list<SymbolRow> $rows
     */
    public function content(RenderKit $services, string $pagePath, string $layer, array $rows): string
    {
        $escaper = $services->escaper;
        $html = sprintf(
            '<div class="symbol-head"><h1><span class="chip chip-layer">layer</span>%s <span class="count">%d</span></h1></div>',
            $escaper->e($layer),
            count($rows),
        ) . "\n";
        $html .= $this->dependencyRow($services, $pagePath, $layer);
        $html .= $this->symbolList->namespaceOverview($services, $pagePath, $rows);

        return $html . $this->symbolList->groups($services, $pagePath, $rows, true);
    }

    /**
     * Renders the allowed dependencies of one layer.
     */
    public function dependencyRow(RenderKit $services, string $pagePath, string $layer): string
    {
        $layers = $services->model->layers;
        if ($layers === null) {
            return '';
        }

        $allowed = $layers->ruleset[$layer] ?? [];
        if ($allowed === []) {
            return '<p class="section-note">This layer may not depend on any other layer.</p>' . "\n";
        }

        $links = [];
        foreach ($allowed as $name) {
            $links[] = $services->escaper->e($name);
        }

        return sprintf(
            '<p class="section-note">May depend on %s.</p>',
            implode(', ', $links),
        ) . "\n";
    }
}
