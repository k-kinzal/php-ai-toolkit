<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use Closure;

use function count;
use function implode;

use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\TypeRenderContext;

use function sprintf;

/**
 * Renders the prose of one PHPDoc block.
 *
 * PHP code fences inside the description are rendered as doctest blocks in
 * place, so examples stay where the author put them. A bare "php" fence is one
 * doctest executes, so it is captioned with the command that runs it on its
 * own; a fence marked anything else, or none, is styled but not offered as
 * runnable.
 */
final class DocTextHtml
{
    /** @readonly */
    private ExampleHtml $example;

    /** @readonly */
    private MarkdownInline $inline;

    /**
     * Creates a doc text renderer from the example renderer.
     */
    public function __construct(?ExampleHtml $example = null, ?MarkdownInline $inline = null)
    {
        $this->example = $example ?? new ExampleHtml();
        $this->inline = $inline ?? new MarkdownInline();
    }

    /**
     * Renders deprecation notice, summary, and description.
     *
     * @param string $symbol the unqualified target name doctest names the examples after, empty when unknown
     */
    public function render(RenderKit $services, ?DocBlock $docBlock, TypeRenderContext $context, string $symbol = ''): string
    {
        if ($docBlock === null) {
            return '';
        }

        $html = $this->deprecationBox($services, $docBlock) . $this->visibilityBox($services, $docBlock);
        if ($docBlock->summary !== '') {
            $html .= '<p class="lede">' . $this->inline->render($docBlock->summary) . '</p>' . "\n";
        }

        if ($docBlock->description !== '') {
            $html .= '<div class="doc-body">' . $services->markdown->render(
                $docBlock->description,
                $this->fenceRenderer($services, $symbol, $this->fenceIndexBase($services, $docBlock)),
            ) . '</div>' . "\n";
        }

        return $html;
    }

    /**
     * Returns the renderer for the code fences of a description.
     *
     * @param int $indexBase how many at-example blocks the docblock carries, which the fences are numbered after
     *
     * @return Closure(string, string): ?string
     */
    public function fenceRenderer(RenderKit $services, string $symbol, int $indexBase): Closure
    {
        $example = $this->example;
        $fenceNumber = 0;

        return static function (string $code, string $language) use ($services, $example, $symbol, $indexBase, &$fenceNumber): ?string {
            if ($language !== 'php' && $language !== '') {
                return null;
            }

            if ($language !== 'php' || $symbol === '') {
                return $example->codeBlock($services, $code) . "\n";
            }

            $fenceNumber++;

            return $example->figure($services, null, $code, true, $example->exampleName($symbol, null, $indexBase + $fenceNumber - 1));
        };
    }

    /**
     * Returns the number doctest gives the first fence of the docblock, minus one.
     *
     * Doctest numbers the at-example blocks of a docblock before its fences, so
     * a fence is identified by its position after all of them.
     */
    public function fenceIndexBase(RenderKit $services, DocBlock $docBlock): int
    {
        return count($services->doctest->tagExamples($services->doctest->cleanDocblock($docBlock->raw)));
    }

    /**
     * Renders the deprecation warning box of a deprecated element.
     */
    public function deprecationBox(RenderKit $services, DocBlock $docBlock): string
    {
        if ($docBlock->deprecated === null) {
            return '';
        }

        $note = $docBlock->deprecated !== '' ? ': ' . $services->escaper->e($docBlock->deprecated) : '.';

        return sprintf('<div class="notice notice-deprecated"><strong>Deprecated</strong>%s</div>', $note) . "\n";
    }

    /**
     * Renders the visibility notice of a declaration that is not public API.
     *
     * The declared scopes are shown as written; scope-guard is what enforces them,
     * so the page states the boundary instead of restating its rules.
     */
    public function visibilityBox(RenderKit $services, DocBlock $docBlock): string
    {
        $scopes = [];
        foreach ($docBlock->visibility as $scope) {
            if ($scope !== 'public') {
                $scopes[] = sprintf('"@visibility %s"', $services->escaper->e($scope));
            }
        }

        if ($scopes === []) {
            return '';
        }

        return sprintf(
            '<div class="notice notice-visibility"><strong>Restricted visibility</strong>: declared %s. Code outside that scope must not name this declaration.</div>',
            implode(' and ', $scopes)
        ) . "\n";
    }
}
