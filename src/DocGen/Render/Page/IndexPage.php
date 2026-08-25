<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page;

use function count;
use function sprintf;

use Toolkit\DocGen\Render\Page\Component\GraphSvg;
use Toolkit\DocGen\Render\Page\Component\SidebarHtml;
use Toolkit\DocGen\Render\PageChrome;
use Toolkit\DocGen\Render\RenderKit;

/**
 * Renders the site index with the package and layer overviews.
 */
final class IndexPage
{
    /** @readonly */
    private PageChrome $chrome;

    /** @readonly */
    private SidebarHtml $sidebar;

    /** @readonly */
    private GraphSvg $graph;

    /** @readonly */
    private SymbolIndex $symbols;

    /**
     * Creates an index page renderer from its collaborators.
     */
    public function __construct(
        ?PageChrome $chrome = null,
        ?SidebarHtml $sidebar = null,
        ?GraphSvg $graph = null,
        ?SymbolIndex $symbols = null,
    ) {
        $this->chrome = $chrome ?? new PageChrome();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->graph = $graph ?? new GraphSvg();
        $this->symbols = $symbols ?? new SymbolIndex();
    }

    /**
     * Renders the complete site index document.
     */
    public function render(RenderKit $services): string
    {
        return $this->chrome->page(
            $services,
            'index.html',
            'Overview',
            $this->description($services),
            '<span class="crumb-current">Overview</span>',
            $this->sidebar->build($services, 'index.html', new SidebarScope(null, null, null, [])),
            $this->content($services),
        );
    }

    /**
     * Describes the documented project in one sentence.
     *
     * What a project says about itself in its own manifest describes it
     * better than anything countable about the site, so the description of
     * the first documented package of the project is preferred.
     */
    public function description(RenderKit $services): string
    {
        foreach ($services->model->packages as $package) {
            if (!$package->isVendor && $package->manifest->description !== '') {
                return $package->manifest->description;
            }
        }

        return sprintf('API documentation of %s.', $services->model->title);
    }

    /**
     * Renders the index content.
     */
    public function content(RenderKit $services): string
    {
        $escaper = $services->escaper;
        $html = sprintf('<div class="symbol-head"><h1>%s</h1></div>', $escaper->e($services->model->title)) . "\n";
        if ($services->model->publicApi) {
            $html .= '<div class="notice notice-public"><strong>Public API documentation</strong>: navigation, listings, counts, and search include only declarations marked <code>@visibility public</code>.</div>' . "\n";
        }

        $html .= $this->packageTable($services);
        $html .= $this->packageGraph($services);
        if ($services->model->warnings !== []) {
            $html .= '<details class="notice notice-warn"><summary>Analysis warnings <span class="count">' . count($services->model->warnings) . '</span></summary><ul>';
            foreach ($services->model->warnings as $warning) {
                $html .= '<li>' . $escaper->e($warning) . '</li>';
            }

            $html .= '</ul></details>' . "\n";
        }

        return $html;
    }

    /**
     * Renders the package summary table.
     */
    public function packageTable(RenderKit $services): string
    {
        $escaper = $services->escaper;
        $statuses = [];
        foreach ($services->model->packages as $package) {
            $statuses[] = $services->diff->packageStatus($package->manifest->name);
        }

        $html = '<section' . $services->diff->combined($statuses)
            . '><h2 id="packages">Packages<a class="anchor" href="#packages">§</a></h2><div class="table-wrap"><table class="symbol-table">';
        foreach ($services->model->packages as $package) {
            $html .= sprintf(
                '<tr%s><td><a href="%s">%s</a>%s</td><td class="pkg-count">%d symbols</td><td>%s</td></tr>',
                $services->diff->mark($services->diff->packageStatus($package->manifest->name)),
                $escaper->e($services->url->packagePage($package->manifest->name)),
                $escaper->e($package->manifest->name),
                $package->isVendor ? sprintf(' <span class="chip chip-sm chip-ghost">%s</span>', $package->isDevDependency ? 'dev dependency' : 'vendor') : '',
                count($this->symbols->inPackage($services, $package->manifest->name)),
                $escaper->e($package->manifest->description),
            );
        }

        return $html . '</table></div></section>' . "\n";
    }

    /**
     * Renders the package dependency graph.
     */
    public function packageGraph(RenderKit $services): string
    {
        if (count($services->model->packages) < 2) {
            return '';
        }

        $nodes = [];
        foreach ($services->model->packages as $package) {
            $nodes[] = [
                'id' => $package->manifest->name,
                'label' => $package->manifest->name,
                'href' => $services->url->packagePage($package->manifest->name),
                'kind' => $package->isVendor ? 'vendor' : 'pkg',
            ];
        }

        $edges = [];
        foreach ($services->model->graph->edges as $edge) {
            $edges[] = ['from' => $edge->from, 'to' => $edge->to, 'kind' => $edge->kind];
        }

        return '<section' . $services->diff->unchanged() . '><h2 id="package-graph">Package Dependencies<a class="anchor" href="#package-graph">§</a></h2>'
            . '<div class="graph-wrap">' . $this->graph->render($nodes, $edges) . '</div>'
            . '<div class="legend"><span class="legend-item legend-require">require</span><span class="legend-item legend-require-dev">require-dev</span><span class="legend-item legend-suggest">suggest</span></div>'
            . '</section>' . "\n";
    }
}
