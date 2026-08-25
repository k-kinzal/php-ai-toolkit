<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Diff;

use function array_slice;

use Closure;

use function count;
use function explode;
use function implode;
use function rtrim;
use function sprintf;
use function str_replace;

use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\LcsMatcher;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\RenderKit;

use function trim;

/**
 * Renders one Markdown document as the merge of its two revisions.
 *
 * Prose is compared by block — a paragraph, a heading, a list, a table, a
 * fenced example — because a block is the unit a reader reads, and because
 * a block is what the renderer turns into one piece of HTML. The blocks
 * are the very ones the plain renderer produces, so the document reads the
 * same as always when the diff marks are switched off.
 */
final class MarkdownDiffHtml
{
    /** @readonly */
    private LcsMatcher $matcher;

    /**
     * Creates a document diff renderer from its matcher.
     */
    public function __construct(?LcsMatcher $matcher = null)
    {
        $this->matcher = $matcher ?? new LcsMatcher();
    }

    /**
     * Renders the merged document of two revisions.
     *
     * @param ?string $base the document as the base revision had it
     * @param ?string $head the document as the head revision has it
     */
    public function render(RenderKit $services, MarkdownRenderer $renderer, ?string $base, ?string $head, ?Closure $fence): string
    {
        $baseBlocks = $base === null ? [] : $this->blocks($renderer, $base, $fence);
        $headBlocks = $head === null ? [] : $this->blocks($renderer, $head, $fence);
        $html = '';
        foreach ($this->matcher->match($this->sources($baseBlocks), $this->sources($headBlocks)) as $operation) {
            $html .= $this->block($services, $baseBlocks, $headBlocks, $operation);
        }

        return $html;
    }

    /**
     * Renders one merged block of the document.
     *
     * @param list<array{source: string, html: string}> $baseBlocks
     * @param list<array{source: string, html: string}> $headBlocks
     * @param array{base: ?int, head: ?int} $operation
     */
    public function block(RenderKit $services, array $baseBlocks, array $headBlocks, array $operation): string
    {
        $headIndex = $operation['head'];
        $baseIndex = $operation['base'];
        if ($headIndex === null) {
            $removed = $baseIndex === null ? null : ($baseBlocks[$baseIndex] ?? null);

            return $removed === null ? '' : $this->wrap($services, DiffStatus::REMOVED, $removed['html']);
        }

        $block = $headBlocks[$headIndex] ?? null;

        return $block === null ? '' : $this->wrap($services, $baseIndex === null ? DiffStatus::ADDED : DiffStatus::SAME, $block['html']);
    }

    /**
     * Wraps one rendered block in its diff state.
     */
    public function wrap(RenderKit $services, string $status, string $html): string
    {
        return sprintf('<div class="doc-block"%s>%s</div>', $services->diff->mark($status), $html);
    }

    /**
     * Splits one document into its rendered blocks.
     *
     * @return list<array{source: string, html: string}>
     */
    public function blocks(MarkdownRenderer $renderer, string $markdown, ?Closure $fence): array
    {
        $lines = explode("\n", str_replace("\r\n", "\n", rtrim($markdown)));
        $blocks = [];
        $index = 0;
        $total = count($lines);
        while ($index < $total) {
            if (trim($lines[$index]) === '') {
                $index++;
                continue;
            }

            $start = $index;
            $block = $renderer->fenceBlock($lines, $index, $fence)
                ?? $renderer->headingBlock($lines, $index)
                ?? $renderer->quoteBlock($lines, $index)
                ?? $renderer->listBlock($lines, $index)
                ?? $renderer->tableBlock($lines, $index)
                ?? $renderer->paragraphBlock($lines, $index);
            $index = $block[1] > $start ? $block[1] : $start + 1;
            $blocks[] = ['source' => implode("\n", array_slice($lines, $start, $index - $start)), 'html' => $block[0]];
        }

        return $blocks;
    }

    /**
     * Lists the source text of every block, in order.
     *
     * @param list<array{source: string, html: string}> $blocks
     *
     * @return list<string>
     */
    public function sources(array $blocks): array
    {
        $sources = [];
        foreach ($blocks as $block) {
            $sources[] = $block['source'];
        }

        return $sources;
    }
}
