<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Diff;

use function sprintf;

use Toolkit\DocGen\Render\RenderKit;

/**
 * Renders the three-way display mode switch of a diff site.
 *
 * The three modes answer three different questions: what the code is now,
 * what changed, and what changed and nothing else. The switch sits next to
 * the theme toggle because both change how the same page is displayed
 * rather than which page is displayed.
 */
final class DiffModeControl
{
    /**
     * The three display modes with their labels and explanations.
     *
     * @var list<array{value: string, label: string, title: string}>
     */
    public const MODES = [
        ['value' => 'off', 'label' => 'Off', 'title' => 'Documentation of the head revision, without diff marks'],
        ['value' => 'inline', 'label' => 'Diff', 'title' => 'Every page with additions and removals marked'],
        ['value' => 'changes', 'label' => 'Changes', 'title' => 'Only what the head revision added, changed, or removed'],
    ];

    /**
     * Renders the mode switch, or nothing outside a diff site.
     */
    public function render(RenderKit $services): string
    {
        if (!$services->diff->isActive()) {
            return '';
        }

        $escaper = $services->escaper;
        $html = sprintf(
            '<span class="diff-range" title="Comparing %s to %s">%s <span class="diff-arrow">→</span> %s</span>',
            $escaper->e($services->diff->baseLabel()),
            $escaper->e($services->diff->headLabel()),
            $escaper->e($services->diff->baseLabel()),
            $escaper->e($services->diff->headLabel()),
        );
        $html .= '<div class="diff-modes" id="diff-modes" role="group" aria-label="Diff display mode">';
        foreach (self::MODES as $mode) {
            $html .= sprintf(
                '<button type="button" class="diff-mode" data-diff-mode="%s" title="%s">%s</button>',
                $escaper->e($mode['value']),
                $escaper->e($mode['title']),
                $escaper->e($mode['label']),
            );
        }

        return $html . '</div>';
    }
}
