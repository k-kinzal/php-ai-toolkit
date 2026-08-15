<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function get_object_vars;

use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;

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
 */
final class RenderKit
{
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
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return get_object_vars($this)[$name] ?? null;
    }
}
