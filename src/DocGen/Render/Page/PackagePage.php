<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function implode;
use function ksort;

use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\DocumentListHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\GraphSvg;
use PhpAiToolkit\DocGen\Render\Page\Component\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\RepositoryLink;

use function sprintf;
use function str_starts_with;

/**
 * Renders the overview page of one package.
 *
 * The page reads from the widest scope to the narrowest: what the package
 * depends on, the architecture layers its symbols fall into, the namespaces
 * inside those layers, and finally the README.
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

    /** @readonly */
    private SymbolListHtml $symbolList;

    /** @readonly */
    private DocumentListHtml $documents;

    /** @readonly */
    private RepositoryLink $repository;

    /**
     * Creates a package page renderer from its collaborators.
     */
    public function __construct(
        ?PageChrome $chrome = null,
        ?SidebarHtml $sidebar = null,
        ?BreadcrumbHtml $breadcrumb = null,
        ?SymbolIndex $symbols = null,
        ?GraphSvg $graph = null,
        ?SymbolListHtml $symbolList = null,
        ?DocumentListHtml $documents = null,
        ?RepositoryLink $repository = null,
    ) {
        $this->chrome = $chrome ?? new PageChrome();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->breadcrumb = $breadcrumb ?? new BreadcrumbHtml();
        $this->symbols = $symbols ?? new SymbolIndex();
        $this->graph = $graph ?? new GraphSvg();
        $this->symbolList = $symbolList ?? new SymbolListHtml();
        $this->documents = $documents ?? new DocumentListHtml();
        $this->repository = $repository ?? new RepositoryLink();
    }

    /**
     * Renders one complete package page document.
     */
    public function render(RenderKit $services, DiscoveredPackage $package, ?string $readme): string
    {
        $pagePath = $services->url->packagePage($package->manifest->name);
        $sections = [];
        if ($this->layerCounts($services, $package->manifest->name) !== []) {
            $sections[] = ['id' => 'layers', 'label' => 'Architecture layers'];
        }

        if ($this->namespaceOverview($services, $pagePath, $package->manifest->name) !== '') {
            $sections[] = ['id' => 'namespaces', 'label' => 'Namespaces'];
        }

        if ($this->documents->documents($services, $package->manifest->name) !== []) {
            $sections[] = ['id' => 'documents', 'label' => 'Documents'];
        }

        if ($readme !== null && $readme !== '') {
            $sections[] = ['id' => 'readme', 'label' => 'README'];
        }

        return $this->chrome->page(
            $services,
            $pagePath,
            $package->manifest->name,
            $this->description($package),
            $this->breadcrumb->build($services, $pagePath, [['label' => $package->manifest->name, 'path' => null]]),
            $this->sidebar->build($services, $pagePath, new SidebarScope($package->manifest->name, null, null, $sections)),
            $this->content($services, $pagePath, $package, $readme),
        );
    }

    /**
     * Describes one package in one sentence.
     */
    public function description(DiscoveredPackage $package): string
    {
        if ($package->manifest->description !== '') {
            return $package->manifest->description;
        }

        return sprintf('The %s package.', $package->manifest->name);
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

        return '<section' . $services->diff->unchanged() . '><h2 id="layers">Architecture layers<a class="anchor" href="#layers">§</a></h2>'
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
        $html .= $this->layerSection($services, $pagePath, $package->manifest->name);
        $html .= $this->namespaceOverview($services, $pagePath, $package->manifest->name);
        $html .= $this->documents->section($services, $pagePath, $package->manifest->name);

        return $html . $this->readmeSection($services, $pagePath, $package->manifest->name, $readme);
    }

    /**
     * Renders the README of one package with resolved document links.
     */
    public function readmeSection(RenderKit $services, string $pagePath, string $packageName, ?string $readme): string
    {
        if ($readme === null || $readme === '') {
            return '';
        }

        $markdown = $services->markdown
            ->withLinks($this->documents->links($services, $pagePath, $packageName, ''))
            ->render($readme, static function (string $code, string $language) use ($services): ?string {
                if ($language === 'php') {
                    return '<pre class="code-block"><code>' . $services->highlighter->highlightSnippet($code) . '</code></pre>' . "\n";
                }

                return null;
            });

        return '<section class="readme"' . $services->diff->unchanged() . '><h2 id="readme">README<a class="anchor" href="#readme">§</a></h2>' . $markdown . '</section>' . "\n";
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
        foreach ([['Repository', $this->repositoryLinks($services, $package)], ['Depends on', $internal], ['Required by', $requiredBy], ['External dependencies', $this->externalDependencies($services, $package)]] as $row) {
            if ($row[1] !== []) {
                $html .= '<div class="relation-row"><span class="relation-label">' . $escaper->e($row[0]) . '</span> ' . implode(', ', $row[1]) . '</div>' . "\n";
            }
        }

        return $html;
    }

    /**
     * Renders where the sources of one package can be browsed.
     *
     * A package of the project answers with the repository the project
     * lives in, which is what an address configured for the site is for; a
     * documented dependency answers with what its own manifest declares,
     * because it is published from somewhere else entirely.
     *
     * @return list<string>
     */
    public function repositoryLinks(RenderKit $services, DiscoveredPackage $package): array
    {
        $url = $package->isVendor ? null : $services->model->repository;
        $url ??= $package->manifest->repository === '' ? null : $package->manifest->repository;
        if ($url === null) {
            return [];
        }

        return [$this->repository->full($services, $url)];
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
        return $this->symbolList->namespaceOverview($services, $pagePath, $this->symbols->inPackage($services, $packageName));
    }
}
