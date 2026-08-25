<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render;

use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Render\Diff\DiffHtml;

/**
 * Shared renderer collaborators for one site generation run.
 *
 * @property-read ProjectModel $model
 * @property-read SiteUrl $url
 * @property-read HtmlText $escaper
 * @property-read PhpHighlighter $highlighter
 * @property-read MarkdownRenderer $markdown
 * @property-read TypeHtml $typeHtml
 * @property-read DoctestExtractor $doctest
 * @property-read AssertionScanner $assertions
 * @property-read DiffHtml $diff
 */
final class RenderKit
{
    /** @readonly */
    private DiffHtml $diff;

    /**
     * Creates the renderer collaborators of one generation run.
     */
    public function __construct(
        /** @readonly */
        private ProjectModel $model,
        /** @readonly */
        private SiteUrl $url,
        /** @readonly */
        private HtmlText $escaper,
        /** @readonly */
        private PhpHighlighter $highlighter,
        /** @readonly */
        private MarkdownRenderer $markdown,
        /** @readonly */
        private TypeHtml $typeHtml,
        /** @readonly */
        private DoctestExtractor $doctest,
        /** @readonly */
        private AssertionScanner $assertions,
        ?DiffHtml $diff = null,
    ) {
        $this->diff = $diff ?? new DiffHtml();
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'diff' => $this->diff,
            'model' => $this->model,
            'url' => $this->url,
            'escaper' => $this->escaper,
            'highlighter' => $this->highlighter,
            'markdown' => $this->markdown,
            'typeHtml' => $this->typeHtml,
            'doctest' => $this->doctest,
            'assertions' => $this->assertions,
            default => null,
        };
    }
}
