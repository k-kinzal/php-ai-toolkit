<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function array_keys;
use function ksort;

use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Renders the navigation sidebar shared by all pages.
 *
 * The sidebar is scoped to the current page rather than to the whole
 * project: it lists the sections of the page, the symbols that sit in the
 * same namespace grouped by kind, and the entry points of the package.
 */
final class SidebarHtml
{
    /** @readonly */
    private SymbolIndex $symbols;

    /**
     * Creates a sidebar renderer from the symbol index.
     */
    public function __construct(?SymbolIndex $symbols = null)
    {
        $this->symbols = $symbols ?? new SymbolIndex();
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
        $html .= $scope->namespace !== null
            ? $this->namespaceBlock($services, $pagePath, $scope)
            : $this->namespaceListBlock($services, $pagePath, $scope->packageName);

        return $html . $this->packageBlock($services, $pagePath, $scope->packageName);
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
                '<li><a href="%s">%s</a>%s</li>',
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
                '<li><a href="#%s">%s</a></li>',
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
                    '<li><a href="%s">%s</a></li>',
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
                '<li><a href="%s" title="%s">%s</a></li>',
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
            $html .= sprintf('<div class="sb-kind">%s</div><ul class="sb-list">', $services->escaper->e(SymbolIndex::KIND_LABELS[$kind]));
            foreach ($kindRows as $row) {
                $active = $scope->activeFqcn !== null && strtolower($row->fqcn) === strtolower($scope->activeFqcn);
                $html .= sprintf(
                    '<li%s><a class="k-%s" href="%s">%s</a></li>',
                    $active ? ' class="is-active"' : '',
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
        $html = sprintf(
            '<nav class="sb-block"><div class="sb-title">Package</div><ul class="sb-list"><li><a href="%s">All items</a></li></ul>',
            $escaper->e($services->url->href($pagePath, $services->url->allItemsPage($packageName))),
        );
        $layers = $this->packageLayers($services, $packageName);
        if ($layers !== []) {
            $html .= '<div class="sb-kind">Layers</div><ul class="sb-list">';
            foreach ($layers as $layer) {
                $html .= sprintf(
                    '<li><a href="%s">%s</a></li>',
                    $escaper->e($services->url->href($pagePath, $services->url->layerPage($packageName, $layer))),
                    $escaper->e($layer),
                );
            }

            $html .= '</ul>';
        }

        return $html . '</nav>';
    }

    /**
     * Lists the architecture layers that contain symbols of one package.
     *
     * @return list<string>
     */
    public function packageLayers(RenderKit $services, string $packageName): array
    {
        $layers = [];
        foreach ($this->symbols->inPackage($services, $packageName) as $row) {
            foreach ($row->layers as $layer) {
                $layers[$layer] = true;
            }
        }

        ksort($layers);

        return array_keys($layers);
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
