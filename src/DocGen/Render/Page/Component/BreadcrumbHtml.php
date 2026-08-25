<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page\Component;

use function implode;
use function sprintf;

use Toolkit\DocGen\Render\RenderKit;

/**
 * Renders the breadcrumb trail of one page.
 */
final class BreadcrumbHtml
{
    /**
     * Renders breadcrumb links from label and target pairs.
     *
     * @param list<array{label: string, path: ?string}> $crumbs
     */
    public function build(RenderKit $services, string $pagePath, array $crumbs): string
    {
        $escaper = $services->escaper;
        $parts = [];
        foreach ($crumbs as $crumb) {
            if ($crumb['path'] !== null) {
                $parts[] = sprintf(
                    '<a href="%s">%s</a>',
                    $escaper->e($services->url->href($pagePath, $crumb['path'])),
                    $escaper->e($crumb['label']),
                );
            } else {
                $parts[] = sprintf('<span class="crumb-current">%s</span>', $escaper->e($crumb['label']));
            }
        }

        return implode('<span class="crumb-sep">::</span>', $parts);
    }
}
