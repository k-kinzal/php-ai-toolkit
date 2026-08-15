<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function implode;
use function ksort;

use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;
use function str_starts_with;
use function strtolower;

/**
 * Renders the overview page of one package.
 */
final class PackagePage
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
    private GraphSvg $graph;

    /**
     * Creates a package page renderer from its collaborators.
     */
    public function __construct(
        ?PageChrome $chrome = null,
        ?SidebarHtml $sidebar = null,
        ?BreadcrumbHtml $breadcrumb = null,
        ?SymbolIndex $symbols = null,
        ?GraphSvg $graph = null,
    ) {
        $this->chrome = $chrome ?? new PageChrome();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->breadcrumb = $breadcrumb ?? new BreadcrumbHtml();
        $this->symbols = $symbols ?? new SymbolIndex();
        $this->graph = $graph ?? new GraphSvg();
    }

    /**
     * Renders one complete package page document.
     */
    public function render(RenderKit $services, DiscoveredPackage $package, ?string $readme): string
    {
        $pagePath = $services->url->packagePage($package->manifest->name);
        $sections = [['id' => 'namespaces', 'label' => 'Namespaces']];
        if ($this->layerCounts($services, $package->manifest->name) !== []) {
            $sections[] = ['id' => 'layers', 'label' => 'Architecture layers'];
        }

        if ($readme !== null && $readme !== '') {
            $sections[] = ['id' => 'readme', 'label' => 'README'];
        }

        return $this->chrome->page(
            $services,
            $pagePath,
            $package->manifest->name,
            $this->breadcrumb->build($services, $pagePath, [['label' => $package->manifest->name, 'path' => null]]),
            $this->sidebar->build($services, $pagePath, new SidebarScope($package->manifest->name, null, null, $sections)),
            $this->content($services, $pagePath, $package, $readme),
        );
    }

    /**
     * Counts the symbols of one package per architecture layer.
     *
     * @return array<string, int>
     */
    public function layerCounts(RenderKit $services, string $packageName): array
    {
        $counts = [];
        foreach ($this->symbols->inPackage($services, $packageName) as $row) {
            foreach ($row->layers as $layer) {
                $counts[$layer] = ($counts[$layer] ?? 0) + 1;
            }
        }

        ksort($counts);

        return $counts;
    }

    /**
     * Renders the architecture layers that contain symbols of one package.
     */
    public function layerSection(RenderKit $services, string $pagePath, string $packageName): string
    {
        $counts = $this->layerCounts($services, $packageName);
        $layers = $services->model->layers;
        if ($counts === [] || $layers === null) {
            return '';
        }

        $nodes = [];
        foreach ($counts as $layer => $count) {
            $nodes[] = [
                'id' => $layer,
                'label' => sprintf('%s (%d)', $layer, $count),
                'href' => $services->url->href($pagePath, $services->url->layerPage($packageName, $layer)),
                'kind' => 'layer',
            ];
        }

        $edges = [];
        foreach ($layers->ruleset as $from => $allowed) {
            foreach ($allowed as $to) {
                if (isset($counts[$from]) && isset($counts[$to])) {
                    $edges[] = ['from' => $from, 'to' => $to, 'kind' => 'allowed'];
                }
            }
        }

        return '<section><h2 id="layers">Architecture layers<a class="anchor" href="#layers">§</a></h2>'
            . '<p class="section-note">Layers and allowed dependencies from <code>deptrac.yaml</code>. Layers without an arrow are dependency-free by rule.</p>'
            . '<div class="graph-wrap">' . $this->graph->render($nodes, $edges) . '</div></section>' . "\n";
    }

    /**
     * Renders the overview content of one package.
     */
    public function content(RenderKit $services, string $pagePath, DiscoveredPackage $package, ?string $readme): string
    {
        $escaper = $services->escaper;
        $html = '<div class="symbol-head">';
        $html .= sprintf(
            '<h1><span class="chip chip-kind k-package">package</span>%s%s</h1>',
            $escaper->e($package->manifest->name),
            $package->isVendor ? sprintf(' <span class="chip chip-sm chip-ghost">%s</span>', $package->isDevDependency ? 'dev dependency' : 'vendor') : '',
        );
        $html .= '</div>' . "\n";
        if ($package->manifest->description !== '') {
            $html .= '<p class="lede">' . $escaper->e($package->manifest->description) . '</p>' . "\n";
        }

        $html .= $this->dependencyRows($services, $pagePath, $package);
        $html .= $this->namespaceOverview($services, $pagePath, $package->manifest->name);
        $html .= $this->layerSection($services, $pagePath, $package->manifest->name);
        if ($readme !== null && $readme !== '') {
            $html .= '<section class="readme"><h2 id="readme">README<a class="anchor" href="#readme">§</a></h2>'
                . $services->markdown->render($readme, static function (string $code, string $language) use ($services): ?string {
                    if ($language === 'php') {
                        return '<pre class="code-block"><code>' . $services->highlighter->highlightSnippet($code) . '</code></pre>' . "\n";
                    }

                    return null;
                })
                . '</section>' . "\n";
        }

        return $html;
    }

    /**
     * Renders the dependency relations of one package.
     */
    public function dependencyRows(RenderKit $services, string $pagePath, DiscoveredPackage $package): string
    {
        $escaper = $services->escaper;
        $name = $package->manifest->name;
        $internal = [];
        $requiredBy = [];
        foreach ($services->model->graph->edges as $edge) {
            if ($edge->from === $name) {
                $internal[] = sprintf(
                    '<a href="%s">%s</a>%s',
                    $escaper->e($services->url->href($pagePath, $services->url->packagePage($edge->to))),
                    $escaper->e($edge->to),
                    $edge->kind !== 'require' ? sprintf(' <span class="chip chip-sm chip-ghost">%s</span>', $escaper->e($edge->kind)) : '',
                );
            }

            if ($edge->to === $name) {
                $requiredBy[] = sprintf(
                    '<a href="%s">%s</a>%s',
                    $escaper->e($services->url->href($pagePath, $services->url->packagePage($edge->from))),
                    $escaper->e($edge->from),
                    $edge->kind !== 'require' ? sprintf(' <span class="chip chip-sm chip-ghost">%s</span>', $escaper->e($edge->kind)) : '',
                );
            }
        }

        $html = '';
        foreach ([['Depends on', $internal], ['Required by', $requiredBy], ['External dependencies', $this->externalDependencies($services, $package)]] as $row) {
            if ($row[1] !== []) {
                $html .= '<div class="relation-row"><span class="relation-label">' . $escaper->e($row[0]) . '</span> ' . implode(', ', $row[1]) . '</div>' . "\n";
            }
        }

        return $html;
    }

    /**
     * Renders the external runtime requirements of one package.
     *
     * @return list<string>
     */
    public function externalDependencies(RenderKit $services, DiscoveredPackage $package): array
    {
        $external = [];
        foreach ($package->manifest->requires as $requirement => $constraint) {
            if ($requirement === 'php' || str_starts_with($requirement, 'ext-')) {
                continue;
            }

            $known = false;
            foreach ($services->model->packages as $candidate) {
                if ($candidate->manifest->name === $requirement) {
                    $known = true;
                    break;
                }
            }

            if (!$known) {
                $external[] = sprintf('<code>%s</code> <span class="dep-constraint">%s</span>', $services->escaper->e($requirement), $services->escaper->e($constraint));
            }
        }

        return $external;
    }


    /**
     * Renders the namespace overview table of one package.
     */
    public function namespaceOverview(RenderKit $services, string $pagePath, string $packageName): string
    {
        $namespaces = [];
        foreach ($services->model->classLikes as $classLike) {
            if ($classLike->packageName === $packageName && !$classLike->isDev) {
                $namespaces[$classLike->namespace][] = $classLike;
            }
        }

        if ($namespaces === []) {
            return '';
        }

        ksort($namespaces);
        $html = '<section><h2 id="namespaces">Namespaces<a class="anchor" href="#namespaces">§</a></h2><div class="table-wrap"><table class="symbol-table">';
        foreach ($namespaces as $namespace => $symbols) {
            $kindCounts = [];
            foreach ($symbols as $symbol) {
                $kindCounts[$symbol->kind] = ($kindCounts[$symbol->kind] ?? 0) + 1;
            }

            $countsHtml = '';
            foreach ($kindCounts as $kind => $kindCount) {
                $countsHtml .= sprintf(
                    ' <span class="ns-count k-%s">%d %s</span>',
                    $kind,
                    $kindCount,
                    $services->escaper->e($kindCount === 1 ? $kind : strtolower(SymbolIndex::KIND_LABELS[$kind] ?? $kind . 's')),
                );
            }

            $html .= sprintf(
                '<tr><td><a href="%s">%s</a></td><td class="ns-counts">%s</td></tr>',
                $services->escaper->e($services->url->href($pagePath, $services->url->namespacePage($packageName, $namespace))),
                $services->escaper->e($namespace === '' ? '(global)' : $namespace),
                $countsHtml,
            );
        }

        return $html . '</table></div></section>' . "\n";
    }
}
