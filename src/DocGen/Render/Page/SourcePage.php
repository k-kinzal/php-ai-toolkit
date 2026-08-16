<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function explode;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Render\Diff\DiffBanner;
use PhpAiToolkit\DocGen\Render\Diff\SourceDiffHtml;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\RenderKit;

use function sprintf;
use function str_replace;

/**
 * Renders one highlighted source file with linkable line anchors.
 */
final class SourcePage
{
    /** @readonly */
    private PageChrome $chrome;

    /** @readonly */
    private BreadcrumbHtml $breadcrumb;

    /** @readonly */
    private SidebarHtml $sidebar;

    /** @readonly */
    private SourceDiffHtml $diffHtml;

    /** @readonly */
    private DiffBanner $banner;

    /**
     * Creates a source page renderer from its collaborators.
     */
    public function __construct(
        ?PageChrome $chrome = null,
        ?BreadcrumbHtml $breadcrumb = null,
        ?SidebarHtml $sidebar = null,
        ?SourceDiffHtml $diffHtml = null,
        ?DiffBanner $banner = null,
    ) {
        $this->chrome = $chrome ?? new PageChrome();
        $this->breadcrumb = $breadcrumb ?? new BreadcrumbHtml();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->diffHtml = $diffHtml ?? new SourceDiffHtml();
        $this->banner = $banner ?? new DiffBanner();
    }

    /**
     * Renders one complete source page document.
     *
     * @param ?string $code the file as the head revision has it
     * @param ?string $baseCode the file as the base revision had it
     */
    public function render(RenderKit $services, string $relativeFile, ?string $code, ?string $baseCode = null): string
    {
        $pagePath = $services->url->sourcePage($relativeFile);
        $crumbs = [
            ['label' => 'src', 'path' => 'index.html'],
            ['label' => $relativeFile, 'path' => null],
        ];

        return $this->chrome->page(
            $services,
            $pagePath,
            $relativeFile,
            $this->breadcrumb->build($services, $pagePath, $crumbs),
            $this->sidebar->build($services, $pagePath, new SidebarScope(null, null, null, [])),
            $this->content($services, $relativeFile, $code, $baseCode),
        );
    }

    /**
     * Renders the numbered source listing.
     *
     * @param ?string $code the file as the head revision has it
     * @param ?string $baseCode the file as the base revision had it
     */
    public function content(RenderKit $services, string $relativeFile, ?string $code, ?string $baseCode = null): string
    {
        $status = $this->statusOf($services, $code, $baseCode);
        $html = sprintf('<div class="symbol-head"><h1 class="source-title">%s</h1></div>', $services->escaper->e($relativeFile)) . "\n";
        $html .= $this->banner->render($services, $status);
        $html .= '<pre class="source"' . $services->diff->mark($status) . '><code>';

        return $html . ($services->diff->isActive()
            ? $this->diffHtml->listing($services, $baseCode, $code)
            : $this->listing($services, $code ?? '')) . '</code></pre>' . "\n";
    }

    /**
     * Renders the plain listing of one revision of a file.
     */
    public function listing(RenderKit $services, string $code): string
    {
        $html = '';
        foreach (explode("\n", $services->highlighter->highlight($code)) as $index => $line) {
            $number = $index + 1;
            $html .= sprintf('<span class="src-line" id="L%d"><a class="ln" href="#L%d">%d</a>%s</span>' . "\n", $number, $number, $number, $line);
        }

        return $html;
    }

    /**
     * Determines the state of one file between the compared revisions.
     *
     * @param ?string $code the file as the head revision has it
     * @param ?string $baseCode the file as the base revision had it
     */
    public function statusOf(RenderKit $services, ?string $code, ?string $baseCode): string
    {
        if (!$services->diff->isActive()) {
            return DiffStatus::SAME;
        }

        if ($code === null) {
            return DiffStatus::REMOVED;
        }

        if ($baseCode === null) {
            return DiffStatus::ADDED;
        }

        return str_replace("\r\n", "\n", $baseCode) === str_replace("\r\n", "\n", $code)
            ? DiffStatus::SAME
            : DiffStatus::MODIFIED;
    }
}
