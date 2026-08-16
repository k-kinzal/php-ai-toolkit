<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;

/**
 * States on the page itself that a whole symbol was added or removed.
 *
 * A page that exists only in one of the two revisions would otherwise look
 * like ordinary documentation of code that is not there.
 */
final class DiffBanner
{
    /**
     * Renders the banner of one page, or nothing when it is not needed.
     *
     * @param string $status the state of the symbol the page documents
     */
    public function render(RenderKit $services, string $status): string
    {
        $diff = $services->diff;
        if (!$diff->isActive() || ($status !== DiffStatus::ADDED && $status !== DiffStatus::REMOVED)) {
            return '';
        }

        return sprintf(
            '<div class="notice diff-banner"%s>%s in %s, compared to %s.</div>' . "\n",
            $diff->mark($status),
            $status === DiffStatus::ADDED ? 'Added' : 'Removed',
            $services->escaper->e($diff->headLabel()),
            $services->escaper->e($diff->baseLabel()),
        );
    }
}
