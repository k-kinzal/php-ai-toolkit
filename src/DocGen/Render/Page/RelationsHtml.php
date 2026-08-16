<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function count;
use function in_array;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;

/**
 * Renders the relationship sections of a class-like page.
 *
 * Inheritance is shown as linked rows, production references are grouped by
 * what they actually do, and test code never appears here: what tests
 * guarantee is answered by the test case section instead.
 */
final class RelationsHtml
{
    /**
     * Reference kinds already covered by the inheritance rows.
     *
     * @var list<string>
     */
    public const STRUCTURAL_KINDS = ['extends', 'implements', 'use-trait'];

    /** @readonly */
    private UsageListHtml $usageList;

    /**
     * Creates a relations renderer from the usage list renderer.
     */
    public function __construct(?UsageListHtml $usageList = null)
    {
        $this->usageList = $usageList ?? new UsageListHtml();
    }

    /**
     * Renders hierarchy relations and the reference groups of one symbol.
     */
    public function build(RenderKit $services, string $pagePath, ClassLikeDoc $classLike): string
    {
        $rows = $this->hierarchyRows($services, $pagePath, $classLike);
        $groups = $this->referenceGroups($services, $pagePath, $classLike);
        if ($rows === '' && $groups === '') {
            return '';
        }

        return '<section class="relations"' . $services->diff->unchanged() . '><h2 id="relations">Relations<a class="anchor" href="#relations">§</a></h2>' . "\n"
            . $rows . $groups . '</section>' . "\n";
    }

    /**
     * Renders the inheritance rows of one symbol.
     */
    public function hierarchyRows(RenderKit $services, string $pagePath, ClassLikeDoc $classLike): string
    {
        $hierarchy = $services->model->hierarchy;
        $html = '';
        if ($classLike->kind === ClassLikeKind::INTERFACE_) {
            $html .= $this->symbolSection($services, $pagePath, 'Implemented by', $hierarchy->implementorsOf($classLike->fqcn));
            $html .= $this->symbolSection($services, $pagePath, 'Extended by', $hierarchy->interfaceExtendersOf($classLike->fqcn));
        }

        if ($classLike->kind === ClassLikeKind::CLASS_) {
            $html .= $this->symbolSection($services, $pagePath, 'Extended by', $hierarchy->subclassesOf($classLike->fqcn));
        }

        if ($classLike->kind === ClassLikeKind::TRAIT_) {
            $html .= $this->symbolSection($services, $pagePath, 'Used by', $hierarchy->traitUsersOf($classLike->fqcn));
        }

        return $html;
    }

    /**
     * Renders one collapsible section of related symbols.
     *
     * Inheritance uses the same disclosure shape as the reference groups,
     * so every relation on the page is read the same way.
     *
     * @param list<string> $names
     */
    public function symbolSection(RenderKit $services, string $pagePath, string $label, array $names): string
    {
        if ($names === []) {
            return '';
        }

        $html = '<details class="usage-details" open><summary>'
            . sprintf('%s <span class="count">%d</span>', $services->escaper->e($label), count($names))
            . '</summary><ul class="usage-list">';
        foreach ($names as $name) {
            $target = $services->model->symbolTable->classLike($name);
            $html .= '<li>' . ($target === null
                ? sprintf('<code>%s</code>', $services->escaper->e($name))
                : sprintf(
                    '<a class="k-%s" href="%s" title="%s">%s</a> <a class="usage-loc" href="%s">%s:%d</a>',
                    $services->escaper->e($target->kind),
                    $services->escaper->e($services->url->href($pagePath, $services->url->classLikePage($target))),
                    $services->escaper->e($target->fqcn),
                    $services->escaper->e($target->shortName),
                    $services->escaper->e($services->url->href($pagePath, $services->url->sourcePage($target->file)) . '#L' . $target->startLine),
                    $services->escaper->e($target->file),
                    $target->startLine,
                )) . '</li>';
        }

        return $html . '</ul></details>' . "\n";
    }

    /**
     * Renders the production references of one symbol, grouped by kind.
     */
    public function referenceGroups(RenderKit $services, string $pagePath, ClassLikeDoc $classLike): string
    {
        $fqcn = $classLike->fqcn;
        $html = '';
        foreach ($services->model->usages->forTypeGrouped($fqcn, false) as $kind => $usages) {
            if (in_array($kind, self::STRUCTURAL_KINDS, true)) {
                continue;
            }

            $own = [];
            foreach ($usages as $usage) {
                if ($usage->fromFqcn !== $fqcn) {
                    $own[] = $usage;
                }
            }

            $html .= $this->usageList->section(
                $services,
                $pagePath,
                UsageListHtml::KIND_LABELS[$kind] ?? $kind,
                $own,
                false,
            );
        }

        return $html;
    }
}
