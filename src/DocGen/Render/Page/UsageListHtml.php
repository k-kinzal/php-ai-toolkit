<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function count;

use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;
use function strrchr;
use function substr;

/**
 * Renders reference lists that link into the documented sources.
 *
 * Symbols that have their own page link to it; symbols without a page, such
 * as test classes, link to their highlighted source instead, so a listing
 * never points at a page that was not generated.
 */
final class UsageListHtml
{
    /**
     * Human-readable labels of the reference kinds.
     *
     * @var array<string, string>
     */
    public const KIND_LABELS = [
        'extends' => 'Extended by',
        'implements' => 'Implemented by',
        'use-trait' => 'Used by',
        'new' => 'Instantiated in',
        'static-call' => 'Static calls',
        'method-call' => 'Method calls',
        'class-const' => 'Constant reads',
        'instanceof' => 'Type checks',
        'attribute' => 'Applied as attribute',
        'type' => 'Type declarations',
        'function-call' => 'Function calls',
    ];

    /**
     * Renders one list of usages.
     *
     * @param list<Usage> $usages
     */
    public function build(RenderKit $services, string $pagePath, array $usages): string
    {
        $html = '<ul class="usage-list">';
        foreach ($usages as $usage) {
            $html .= '<li>' . $this->item($services, $pagePath, $usage) . '</li>';
        }

        return $html . '</ul>';
    }

    /**
     * Renders one usage entry with origin and location links.
     */
    public function item(RenderKit $services, string $pagePath, Usage $usage): string
    {
        $escaper = $services->escaper;
        $origin = $escaper->e($usage->file);
        if ($usage->fromFqcn !== null) {
            $label = $usage->fromFqcn . ($usage->fromMember !== null ? '::' . $usage->fromMember . '()' : '');
            $origin = $this->symbolLink($services, $pagePath, $usage->fromFqcn, $label, $usage->file, $usage->line);
        }

        return sprintf(
            '<span class="usage-kind">%s</span> %s <a class="usage-loc" href="%s">%s:%d</a>',
            $escaper->e($usage->kind),
            $origin,
            $escaper->e($services->url->href($pagePath, $services->url->sourcePage($usage->file)) . '#L' . $usage->line),
            $escaper->e($usage->file),
            $usage->line,
        );
    }

    /**
     * Renders one outgoing call entry, naming the called symbol.
     */
    public function callItem(RenderKit $services, string $pagePath, Usage $usage): string
    {
        $label = $this->shortName($usage->targetFqcn) . ($usage->member !== null ? '::' . $usage->member . '()' : '');

        return sprintf(
            '<span class="usage-kind">%s</span> %s <a class="usage-loc" href="%s">line %d</a>',
            $services->escaper->e($usage->kind),
            $this->symbolLink($services, $pagePath, $usage->targetFqcn, $label, $usage->file, $usage->line),
            $services->escaper->e($services->url->href($pagePath, $services->url->sourcePage($usage->file)) . '#L' . $usage->line),
            $usage->line,
        );
    }

    /**
     * Links a symbol to its page, or to its source when it has none.
     */
    public function symbolLink(RenderKit $services, string $pagePath, string $fqcn, string $label, string $fallbackFile, int $fallbackLine): string
    {
        $escaper = $services->escaper;
        $target = $services->model->symbolTable->classLike($fqcn);
        if ($target === null) {
            return sprintf('<code>%s</code>', $escaper->e($label));
        }

        $href = $target->isDev
            ? $services->url->href($pagePath, $services->url->sourcePage($target->file)) . '#L' . $target->startLine
            : $services->url->href($pagePath, $services->url->classLikePage($target));

        return sprintf(
            '<a href="%s" title="%s">%s</a>%s',
            $escaper->e($href),
            $escaper->e($fqcn),
            $escaper->e($label),
            $target->isDev ? ' <span class="chip chip-sm chip-test">test</span>' : '',
        );
    }

    /**
     * Renders a collapsible reference section when usages exist.
     *
     * @param list<Usage> $usages
     */
    public function section(RenderKit $services, string $pagePath, string $title, array $usages, bool $open): string
    {
        if ($usages === []) {
            return '';
        }

        return '<details class="usage-details"' . ($open ? ' open' : '') . '><summary>'
            . sprintf('%s <span class="count">%d</span>', $services->escaper->e($title), count($usages))
            . '</summary>' . $this->build($services, $pagePath, $usages) . '</details>' . "\n";
    }

    /**
     * Renders a collapsible section of outgoing calls.
     *
     * @param list<Usage> $usages
     */
    public function callSection(RenderKit $services, string $pagePath, string $title, array $usages): string
    {
        if ($usages === []) {
            return '';
        }

        $html = '<details class="usage-details"><summary>'
            . sprintf('%s <span class="count">%d</span>', $services->escaper->e($title), count($usages))
            . '</summary><ul class="usage-list">';
        foreach ($usages as $usage) {
            $html .= '<li>' . $this->callItem($services, $pagePath, $usage) . '</li>';
        }

        return $html . '</ul></details>' . "\n";
    }

    /**
     * Returns the short name of a fully qualified symbol name.
     */
    public function shortName(string $fqcn): string
    {
        $tail = strrchr($fqcn, '\\');

        return $tail === false ? $fqcn : substr($tail, 1);
    }
}
