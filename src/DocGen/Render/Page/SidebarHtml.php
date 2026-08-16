<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Renders the navigation sidebar shared by all pages.
 *
 * The sidebar is scoped to the current page rather than to the whole
 * project: it lists the sections of the page, then narrows from the widest
 * scope to the narrowest — the entry points and layers of the package
 * first, then the namespace with the symbols that sit next to this page.
 */
final class SidebarHtml
{
    /** @readonly */
    private SymbolIndex $symbols;

    /** @readonly */
    private DocumentListHtml $documents;

    /**
     * Creates a sidebar renderer from the symbol and document indexes.
     */
    public function __construct(?SymbolIndex $symbols = null, ?DocumentListHtml $documents = null)
    {
        $this->symbols = $symbols ?? new SymbolIndex();
        $this->documents = $documents ?? new DocumentListHtml();
    }

    /**
     * Renders the sidebar of one page.
     */
    public function build(RenderKit $services, string $pagePath, SidebarScope $scope): string
    {
        $escaper = $services->escaper;
        $html = sprintf(
            '<div class="sb-head"><a class="sb-site" href="%s">%s</a></div>',
            $escaper->e($services->url->href($pagePath, 'index.html')),
            $escaper->e($services->model->title),
        );

        if ($scope->packageName === null) {
            return $html . $this->packageList($services, $pagePath);
        }

        $html .= sprintf(
            '<div class="sb-pkg"><a href="%s">%s</a></div>',
            $escaper->e($services->url->href($pagePath, $services->url->packagePage($scope->packageName))),
            $escaper->e($scope->packageName),
        );
        $html .= $this->pageSections($services, $scope);
        $html .= $this->packageBlock($services, $pagePath, $scope->packageName);

        return $html . ($scope->namespace !== null
            ? $this->namespaceBlock($services, $pagePath, $scope)
            : $this->namespaceListBlock($services, $pagePath, $scope->packageName));
    }

    /**
     * Renders the package list shown on the site index.
     */
    public function packageList(RenderKit $services, string $pagePath): string
    {
        $escaper = $services->escaper;
        $html = '<nav class="sb-block"><div class="sb-title">Packages</div><ul class="sb-list">';
        foreach ($services->model->packages as $package) {
            $html .= sprintf(
                '<li%s><a href="%s">%s</a>%s</li>',
                $services->diff->mark($services->diff->packageStatus($package->manifest->name)),
                $escaper->e($services->url->href($pagePath, $services->url->packagePage($package->manifest->name))),
                $escaper->e($package->manifest->name),
                $package->isVendor ? sprintf('<span class="sb-note">%s</span>', $package->isDevDependency ? 'dev' : 'vendor') : '',
            );
        }

        return $html . '</ul></nav>';
    }

    /**
     * Renders the anchor list of the current page's own sections.
     */
    public function pageSections(RenderKit $services, SidebarScope $scope): string
    {
        if ($scope->sections === []) {
            return '';
        }

        $html = '<nav class="sb-block"><div class="sb-title">On this page</div><ul class="sb-list">';
        foreach ($scope->sections as $section) {
            $html .= sprintf(
                '<li%s><a href="#%s">%s</a></li>',
                $services->diff->mark($section['status'] ?? DiffStatus::SAME),
                $services->escaper->e($section['id']),
                $services->escaper->e($section['label']),
            );
        }

        return $html . '</ul></nav>';
    }

    /**
     * Renders the sibling symbols and child namespaces of the current scope.
     */
    public function namespaceBlock(RenderKit $services, string $pagePath, SidebarScope $scope): string
    {
        $escaper = $services->escaper;
        $packageName = (string) $scope->packageName;
        $namespace = (string) $scope->namespace;
        $html = sprintf(
            '<nav class="sb-block"><div class="sb-title"><a href="%s">In %s</a></div>',
            $escaper->e($services->url->href($pagePath, $services->url->namespacePage($packageName, $namespace))),
            $escaper->e($namespace === '' ? 'global namespace' : $namespace),
        );
        $children = $this->symbols->childNamespaces($services, $packageName, $namespace);
        if ($children !== []) {
            $html .= '<div class="sb-kind">Namespaces</div><ul class="sb-list">';
            foreach ($children as $child) {
                $html .= sprintf(
                    '<li%s><a href="%s">%s</a></li>',
                    $services->diff->mark($services->diff->namespaceStatus($packageName, $child)),
                    $escaper->e($services->url->href($pagePath, $services->url->namespacePage($packageName, $child))),
                    $escaper->e($this->lastSegment($child)),
                );
            }

            $html .= '</ul>';
        }

        return $html . $this->kindLists($services, $pagePath, $scope) . '</nav>';
    }

    /**
     * Renders every namespace of a package, for pages without a namespace.
     *
     * Package-wide pages have no siblings to list, so the sidebar offers
     * the namespaces instead of standing nearly empty.
     */
    public function namespaceListBlock(RenderKit $services, string $pagePath, string $packageName): string
    {
        $namespaces = $this->symbols->namespacesOf($services, $packageName);
        if ($namespaces === []) {
            return '';
        }

        $html = '<nav class="sb-block"><div class="sb-title">Namespaces</div><ul class="sb-list">';
        foreach ($namespaces as $namespace) {
            $html .= sprintf(
                '<li%s><a href="%s" title="%s">%s</a></li>',
                $services->diff->mark($services->diff->namespaceStatus($packageName, $namespace)),
                $services->escaper->e($services->url->href($pagePath, $services->url->namespacePage($packageName, $namespace))),
                $services->escaper->e($namespace === '' ? 'global namespace' : $namespace),
                $services->escaper->e($namespace === '' ? '(global)' : $namespace),
            );
        }

        return $html . '</ul></nav>';
    }

    /**
     * Renders the kind-grouped symbol lists of the current namespace.
     */
    public function kindLists(RenderKit $services, string $pagePath, SidebarScope $scope): string
    {
        $rows = $this->symbols->inNamespace($services, (string) $scope->packageName, (string) $scope->namespace);
        $html = '';
        foreach ($this->symbols->byKind($rows) as $kind => $kindRows) {
            $statuses = [];
            foreach ($kindRows as $row) {
                $statuses[] = $row->status;
            }

            $html .= sprintf(
                '<div class="sb-kind"%s>%s</div><ul class="sb-list">',
                $services->diff->combined($statuses),
                $services->escaper->e(SymbolIndex::KIND_LABELS[$kind]),
            );
            foreach ($kindRows as $row) {
                $active = $scope->activeFqcn !== null && strtolower($row->fqcn) === strtolower($scope->activeFqcn);
                $html .= sprintf(
                    '<li%s%s><a class="k-%s" href="%s">%s</a></li>',
                    $active ? ' class="is-active"' : '',
                    $services->diff->mark($row->status),
                    $services->escaper->e($row->kind),
                    $services->escaper->e($services->url->href($pagePath, $row->page)),
                    $services->escaper->e($row->name),
                );
            }

            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Renders the package-wide entry points of the sidebar.
     */
    public function packageBlock(RenderKit $services, string $pagePath, string $packageName): string
    {
        $escaper = $services->escaper;
        $documents = $this->documents->documents($services, $packageName);
        $documentStatuses = [];
        foreach ($documents as $document) {
            $documentStatuses[] = $services->diff->documentStatus($packageName, $document->path);
        }

        $html = sprintf(
            '<nav class="sb-block"><div class="sb-title">Package</div><ul class="sb-list"><li%s><a href="%s">All items</a></li>%s</ul>',
            $services->diff->mark($services->diff->packageStatus($packageName)),
            $escaper->e($services->url->href($pagePath, $services->url->allItemsPage($packageName))),
            $documents === [] ? '' : sprintf(
                '<li%s><a href="%s#documents">Documents</a></li>',
                $services->diff->combined($documentStatuses),
                $escaper->e($services->url->href($pagePath, $services->url->packagePage($packageName))),
            ),
        );

        return $html . $this->layerBlock($services, $pagePath, $packageName) . '</nav>';
    }

    /**
     * Renders the architecture layer list of one package.
     *
     * A layer is as changed as the symbols it holds, so the navigation
     * narrows to the layers a revision touched when only changes are asked
     * for, instead of listing an architecture nothing happened in.
     */
    public function layerBlock(RenderKit $services, string $pagePath, string $packageName): string
    {
        $layers = $this->packageLayers($services, $packageName);
        if ($layers === []) {
            return '';
        }

        $statuses = $this->layerStatuses($services, $packageName);
        $combined = [];
        foreach ($layers as $layer) {
            $combined[] = $statuses[$layer] ?? DiffStatus::SAME;
        }

        $html = sprintf('<div class="sb-kind"%s>Layers</div><ul class="sb-list">', $services->diff->combined($combined));
        foreach ($layers as $layer) {
            $html .= sprintf(
                '<li%s><a href="%s">%s</a></li>',
                $services->diff->mark($statuses[$layer] ?? DiffStatus::SAME),
                $services->escaper->e($services->url->href($pagePath, $services->url->layerPage($packageName, $layer))),
                $services->escaper->e($layer),
            );
        }

        return $html . '</ul>';
    }

    /**
     * Combines the state of the symbols of every architecture layer.
     *
     * @return array<string, string>
     */
    public function layerStatuses(RenderKit $services, string $packageName): array
    {
        return $this->symbols->layerStatuses($services, $packageName);
    }

    /**
     * Lists the architecture layers that contain symbols of one package.
     *
     * @return list<string>
     */
    public function packageLayers(RenderKit $services, string $packageName): array
    {
        return $this->symbols->layersOf($services, $packageName);
    }

    /**
     * Returns the last segment of a namespace name.
     */
    public function lastSegment(string $namespace): string
    {
        $position = strrpos($namespace, '\\');

        return $position === false ? $namespace : substr($namespace, $position + 1);
    }
}
