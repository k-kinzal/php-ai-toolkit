<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page;

use function array_merge;
use function count;
use function explode;
use function sprintf;
use function strrpos;
use function substr;

use Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Render\Page\Component\SidebarHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Render\PageChrome;
use Toolkit\DocGen\Render\RenderKit;

/**
 * Renders the symbol listing page of one namespace in one package.
 */
final class NamespacePage
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
     * Creates a namespace page renderer from its collaborators.
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
     * Renders one complete namespace page document.
     */
    public function render(RenderKit $services, string $packageName, string $namespace): string
    {
        $pagePath = $services->url->namespacePage($packageName, $namespace);
        $rows = $this->symbols->inNamespace($services, $packageName, $namespace);
        $sections = $this->symbolList->sections($rows);
        if ($this->symbols->childNamespaces($services, $packageName, $namespace) !== []) {
            $sections = array_merge([['id' => 'namespaces', 'label' => 'Namespaces']], $sections);
        }

        return $this->chrome->page(
            $services,
            $pagePath,
            $namespace,
            sprintf('The %s namespace of the %s package.', $namespace, $packageName),
            $this->breadcrumb->build($services, $pagePath, $this->crumbs($services, $packageName, $namespace)),
            $this->sidebar->build($services, $pagePath, new SidebarScope($packageName, $namespace, null, $sections)),
            $this->content($services, $pagePath, $packageName, $namespace, $rows),
        );
    }

    /**
     * Builds the breadcrumb trail of one namespace, one crumb per segment.
     *
     * @return list<array{label: string, path: ?string}>
     */
    public function crumbs(RenderKit $services, string $packageName, string $namespace): array
    {
        $crumbs = [['label' => $packageName, 'path' => $services->url->packagePage($packageName)]];
        $walked = '';
        foreach (explode('\\', $namespace) as $segment) {
            $walked = $walked === '' ? $segment : $walked . '\\' . $segment;
            $crumbs[] = [
                'label' => $segment,
                'path' => $walked === $namespace ? null : $services->url->namespacePage($packageName, $walked),
            ];
        }

        return $crumbs;
    }

    /**
     * Renders the listing content of one namespace.
     *
     * @param list<SymbolRow> $rows
     */
    public function content(RenderKit $services, string $pagePath, string $packageName, string $namespace, array $rows): string
    {
        $html = sprintf(
            '<div class="symbol-head"><h1><span class="chip chip-kind k-namespace">namespace</span>%s</h1></div>',
            $services->escaper->e($namespace),
        ) . "\n";
        $html .= $this->childSection($services, $pagePath, $packageName, $namespace);

        return $html . $this->symbolList->groups($services, $pagePath, $rows);
    }

    /**
     * Renders the child namespace listing of one namespace.
     */
    public function childSection(RenderKit $services, string $pagePath, string $packageName, string $namespace): string
    {
        $children = $this->symbols->childNamespaces($services, $packageName, $namespace);
        if ($children === []) {
            return '';
        }

        $statuses = [];
        foreach ($children as $child) {
            $statuses[] = $services->diff->namespaceStatus($packageName, $child);
        }

        $html = sprintf(
            '<section class="items"%s id="namespaces"><h2>Namespaces <span class="count">%d</span><a class="anchor" href="#namespaces">§</a></h2><div class="table-wrap"><table class="item-table">',
            $services->diff->combined($statuses),
            count($children),
        );
        foreach ($children as $child) {
            $position = strrpos($child, '\\');
            $html .= sprintf(
                '<tr%s><td><a class="item-name k-namespace" href="%s">%s</a></td><td class="item-summary">%s</td></tr>',
                $services->diff->mark($services->diff->namespaceStatus($packageName, $child)),
                $services->escaper->e($services->url->href($pagePath, $services->url->namespacePage($packageName, $child))),
                $services->escaper->e($position === false ? $child : substr($child, $position + 1)),
                $services->escaper->e($child),
            );
        }

        return $html . '</table></div></section>' . "\n";
    }
}
