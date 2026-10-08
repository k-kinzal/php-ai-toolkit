<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page\Component;

use function count;
use function ksort;
use function sprintf;
use function strtolower;

use Toolkit\DocGen\Diff\DiffStatus;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\Page\SymbolIndex;
use Toolkit\DocGen\Render\RenderKit;

/**
 * Renders kind-grouped symbol listings.
 *
 * Namespace, layer, and all-item pages share this listing so a symbol is
 * always presented the same way, whichever route led to it.
 */
final class SymbolListHtml
{
    /** @readonly */
    private MarkdownInline $inline;

    /** @readonly */
    private SymbolIndex $symbols;

    /**
     * Creates a symbol listing renderer.
     */
    public function __construct(?MarkdownInline $inline = null, ?SymbolIndex $symbols = null)
    {
        $this->inline = $inline ?? new MarkdownInline();
        $this->symbols = $symbols ?? new SymbolIndex();
    }

    /**
     * Renders one section per kind, each anchored by its kind name.
     *
     * @param list<SymbolRow> $rows
     */
    public function groups(RenderKit $services, string $pagePath, array $rows, bool $withNamespace = false): string
    {
        $html = '';
        foreach ($this->symbols->byKind($rows) as $kind => $kindRows) {
            $html .= sprintf(
                '<section class="items"%s id="%s"><h2>%s <span class="count">%d</span><a class="anchor" href="#%s">§</a></h2>',
                $services->diff->combined($this->statuses($kindRows)),
                $services->escaper->e(SymbolIndex::KIND_ANCHORS[$kind] ?? $kind),
                $services->escaper->e(SymbolIndex::KIND_LABELS[$kind]),
                count($kindRows),
                $services->escaper->e(SymbolIndex::KIND_ANCHORS[$kind] ?? $kind),
            );
            $html .= $this->table($services, $pagePath, $kindRows, $withNamespace) . '</section>' . "\n";
        }

        return $html;
    }

    /**
     * Lists explicit public API in this page's scope, including the base revision.
     *
     * @param list<SymbolRow> $rows
     *
     * @return list<SymbolRow>
     */
    public function publicApiRows(RenderKit $services, array $rows): array
    {
        $public = [];
        foreach ($rows as $row) {
            $included = $row->kind === 'function'
                ? $services->model->isPublicApiFunction($row->fqcn)
                : $services->model->isPublicApiClassLike($row->fqcn);
            if ($included) {
                $public[] = $row;
            }
        }

        return $this->symbols->sorted($public);
    }

    /**
     * Builds the public API sidebar anchor with the status of its declarations.
     *
     * @param list<SymbolRow> $rows
     *
     * @return array{id: string, label: string, status: string}
     */
    public function publicApiAnchor(RenderKit $services, array $rows): array
    {
        return [
            'id' => 'public-api',
            'label' => 'Public API',
            'status' => $services->diff->combine($this->statuses($this->publicApiRows($services, $rows))),
        ];
    }

    /**
     * Renders the public API table before the rest of a scope's documentation.
     *
     * An empty section makes the absence of an explicit public contract visible.
     *
     * @param list<SymbolRow> $rows
     */
    public function publicApiSection(RenderKit $services, string $pagePath, array $rows, bool $withNamespace = false): string
    {
        $public = $this->publicApiRows($services, $rows);
        $html = sprintf(
            '<section class="items"%s id="public-api"><h2>Public API <span class="count">%d</span><a class="anchor" href="#public-api">§</a></h2>',
            $services->diff->combined($this->statuses($public)),
            count($public),
        );
        $html .= '<p class="section-note">Declarations explicitly marked <code>@visibility public</code> in this scope.</p>';
        $html .= $public === []
            ? '<p class="section-note">No declarations in this scope are marked as public API.</p>'
            : $this->table($services, $pagePath, $public, $withNamespace, true);

        return $html . '</section>' . "\n";
    }

    /**
     * Renders one table of symbol rows.
     *
     * Listings that span namespaces show the namespace of every row, so a
     * name is never ambiguous; a namespace listing omits the column.
     *
     * @param list<SymbolRow> $rows
     */
    public function table(RenderKit $services, string $pagePath, array $rows, bool $withNamespace = false, bool $withKind = false): string
    {
        $html = '<div class="table-wrap"><table class="item-table">';
        if ($withKind) {
            $html .= '<thead><tr><th scope="col">Kind</th><th scope="col">Symbol</th>'
                . ($withNamespace ? '<th scope="col">Namespace</th>' : '')
                . '<th scope="col">Summary</th></tr></thead><tbody>';
        }

        foreach ($rows as $row) {
            $html .= sprintf(
                '<tr%s>%s<td><a class="item-name k-%s" href="%s">%s</a>%s</td>%s<td class="item-summary">%s</td></tr>',
                $services->diff->mark($row->status),
                $withKind ? '<td>' . $services->escaper->e($row->kind) . '</td>' : '',
                $services->escaper->e($row->kind),
                $services->escaper->e($services->url->href($pagePath, $row->page)),
                $services->escaper->e($row->name),
                $this->visibilityBadges($services, $row),
                $withNamespace ? $this->namespaceCell($services, $pagePath, $row) : '',
                $this->inline->render($row->summary),
            );
        }

        return $html . ($withKind ? '</tbody>' : '') . '</table></div>';
    }

    /**
     * Renders the declared API status beside one listed symbol.
     */
    public function visibilityBadges(RenderKit $services, SymbolRow $row): string
    {
        $badges = '';
        foreach ($row->visibility as $scope) {
            $public = strtolower($scope) === 'public';
            $badges .= sprintf(
                ' <span class="chip chip-sm %s" title="@visibility %s">%s</span>',
                $public ? 'chip-public' : 'chip-visibility',
                $services->escaper->e($scope),
                $public ? 'public API' : '@visibility ' . $services->escaper->e($scope),
            );
        }

        return $badges;
    }

    /**
     * Renders the namespace overview table of a listing.
     *
     * A listing that spans namespaces opens with the namespaces it covers,
     * so the shape of the scope is read before the individual symbols.
     *
     * @param list<SymbolRow> $rows
     */
    public function namespaceOverview(RenderKit $services, string $pagePath, array $rows): string
    {
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row->namespace][] = $row;
        }

        if ($groups === []) {
            return '';
        }

        ksort($groups);
        $html = '<section' . $services->diff->combined($this->statuses($rows))
            . '><h2 id="namespaces">Namespaces<a class="anchor" href="#namespaces">§</a></h2><div class="table-wrap"><table class="symbol-table">';
        foreach ($groups as $groupRows) {
            $html .= $this->namespaceRow($services, $pagePath, $groupRows);
        }

        return $html . '</table></div></section>' . "\n";
    }

    /**
     * Renders one namespace row with its symbol counts per kind.
     *
     * @param non-empty-list<SymbolRow> $rows
     */
    public function namespaceRow(RenderKit $services, string $pagePath, array $rows): string
    {
        $namespace = $rows[0]->namespace;
        $packageName = $this->packageOf($services, $rows[0]);
        $label = $services->escaper->e($namespace === '' ? '(global)' : $namespace);

        return sprintf(
            '<tr%s><td>%s</td><td class="ns-counts">%s</td></tr>',
            $services->diff->combined($this->statuses($rows)),
            $packageName === '' ? $label : sprintf(
                '<a href="%s">%s</a>',
                $services->escaper->e($services->url->href($pagePath, $services->url->namespacePage($packageName, $namespace))),
                $label,
            ),
            $this->kindCounts($services, $rows),
        );
    }

    /**
     * Renders the per-kind symbol counts of one listing.
     *
     * @param list<SymbolRow> $rows
     */
    public function kindCounts(RenderKit $services, array $rows): string
    {
        $html = '';
        foreach ($this->symbols->byKind($rows) as $kind => $kindRows) {
            $count = count($kindRows);
            $html .= sprintf(
                ' <span class="ns-count k-%s">%d %s</span>',
                $services->escaper->e($kind),
                $count,
                $services->escaper->e($count === 1 ? $kind : strtolower(SymbolIndex::KIND_LABELS[$kind])),
            );
        }

        return $html;
    }

    /**
     * Renders the namespace cell of one row, linking to its listing.
     */
    public function namespaceCell(RenderKit $services, string $pagePath, SymbolRow $row): string
    {
        $packageName = $this->packageOf($services, $row);
        if ($row->namespace === '' || $packageName === '') {
            return sprintf('<td class="item-ns">%s</td>', $services->escaper->e($row->namespace));
        }

        return sprintf(
            '<td class="item-ns"><a href="%s">%s</a></td>',
            $services->escaper->e($services->url->href($pagePath, $services->url->namespacePage($packageName, $row->namespace))),
            $services->escaper->e($row->namespace),
        );
    }

    /**
     * Returns the package name that owns one symbol row.
     */
    public function packageOf(RenderKit $services, SymbolRow $row): string
    {
        $classLike = $services->model->symbolTable->classLike($row->fqcn);
        if ($classLike !== null) {
            return $classLike->packageName;
        }

        $function = $services->model->symbolTable->functionNamed($row->fqcn);

        return $function !== null ? $function->packageName : '';
    }

    /**
     * Lists the diff states of a group of rows.
     *
     * @param list<SymbolRow> $rows
     *
     * @return list<string>
     */
    public function statuses(array $rows): array
    {
        $statuses = [];
        foreach ($rows as $row) {
            $statuses[] = $row->status;
        }

        return $statuses;
    }

    /**
     * Renders the sidebar section anchors of a kind-grouped listing.
     *
     * @param list<SymbolRow> $rows
     *
     * @return list<array{id: string, label: string, status: string}>
     */
    public function sections(array $rows): array
    {
        $sections = [];
        foreach ($this->symbols->byKind($rows) as $kind => $kindRows) {
            $sections[] = [
                'id' => SymbolIndex::KIND_ANCHORS[$kind] ?? $kind,
                'label' => SymbolIndex::KIND_LABELS[$kind],
                'status' => (new DiffStatus())->combine($this->statuses($kindRows)),
            ];
        }

        return $sections;
    }
}
