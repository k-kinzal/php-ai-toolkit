<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page\Component;

use function count;
use function ksort;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;
use function strtolower;

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
     * Renders one table of symbol rows.
     *
     * Listings that span namespaces show the namespace of every row, so a
     * name is never ambiguous; a namespace listing omits the column.
     *
     * @param list<SymbolRow> $rows
     */
    public function table(RenderKit $services, string $pagePath, array $rows, bool $withNamespace = false): string
    {
        $html = '<div class="table-wrap"><table class="item-table">';
        foreach ($rows as $row) {
            $html .= sprintf(
                '<tr%s><td><a class="item-name k-%s" href="%s">%s</a></td>%s<td class="item-summary">%s</td></tr>',
                $services->diff->mark($row->status),
                $services->escaper->e($row->kind),
                $services->escaper->e($services->url->href($pagePath, $row->page)),
                $services->escaper->e($row->name),
                $withNamespace ? $this->namespaceCell($services, $pagePath, $row) : '',
                $this->inline->render($row->summary),
            );
        }

        return $html . '</table></div>';
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
