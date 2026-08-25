<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render;

use function array_map;
use function array_slice;

use Closure;

use function count;
use function explode;
use function implode;
use function is_string;
use function min;
use function preg_match;
use function preg_split;
use function rtrim;
use function str_replace;
use function strlen;
use function trim;

/**
 * Renders a practical subset of Markdown into HTML.
 *
 * Fenced code blocks can be delegated to a caller-provided renderer so PHP
 * examples are highlighted and doctest markers are styled consistently.
 */
final class MarkdownRenderer
{
    /** @readonly */
    private MarkdownInline $inline;

    /** @readonly */
    private HtmlText $escaper;

    /**
     * Creates a Markdown renderer from an inline renderer.
     */
    public function __construct(?MarkdownInline $inline = null, ?HtmlText $escaper = null)
    {
        $this->inline = $inline ?? new MarkdownInline();
        $this->escaper = $escaper ?? new HtmlText();
    }

    /**
     * Returns a renderer that resolves links against rendered documents.
     */
    public function withLinks(MarkdownLinks $links): self
    {
        return new self(new MarkdownInline($links), $this->escaper);
    }

    /**
     * Renders one Markdown text into HTML.
     *
     * The fence closure receives the code and the info string and may return
     * HTML, or null to use the default preformatted rendering.
     */
    public function render(string $markdown, ?Closure $fence = null): string
    {
        $lines = explode("\n", str_replace("\r\n", "\n", rtrim($markdown)));
        $html = '';
        $index = 0;
        $total = count($lines);
        while ($index < $total) {
            $line = $lines[$index];
            if (trim($line) === '') {
                $index++;
                continue;
            }

            $block = $this->fenceBlock($lines, $index, $fence)
                ?? $this->headingBlock($lines, $index)
                ?? $this->quoteBlock($lines, $index)
                ?? $this->listBlock($lines, $index)
                ?? $this->tableBlock($lines, $index)
                ?? $this->paragraphBlock($lines, $index);
            $html .= $block[0];
            $index = $block[1];
        }

        return $html;
    }

    /**
     * Consumes a fenced code block, or returns null.
     *
     * @param list<string> $lines
     *
     * @return ?array{string, int}
     */
    public function fenceBlock(array $lines, int $index, ?Closure $fence): ?array
    {
        if (preg_match('/^```([A-Za-z0-9+-]*)\s*$/', trim($lines[$index]), $match) !== 1) {
            return null;
        }

        $language = $match[1];
        $code = [];
        $index++;
        foreach (array_slice($lines, $index, count($lines) - $index, true) as $lineIndex => $line) {
            if (trim($line) === '```') {
                $index = $lineIndex;
                break;
            }

            $code[] = $line;
            $index = $lineIndex + 1;
        }

        $codeText = implode("\n", $code);
        $custom = $fence !== null ? $fence($codeText, $language) : null;
        $html = is_string($custom) ? $custom : '<pre class="code-block"><code>' . $this->escaper->e($codeText) . '</code></pre>' . "\n";

        return [$html, min($index + 1, count($lines))];
    }

    /**
     * Consumes a heading or horizontal rule line, or returns null.
     *
     * @param list<string> $lines
     *
     * @return ?array{string, int}
     */
    public function headingBlock(array $lines, int $index): ?array
    {
        $line = trim($lines[$index]);
        if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $match) === 1) {
            $level = strlen($match[1]) + 1;
            $level = min($level, 6);

            return ['<h' . $level . '>' . $this->inline->render(trim($match[2], ' #')) . '</h' . $level . ">\n", $index + 1];
        }

        if (preg_match('/^([-*_])\1{2,}$/', $line) === 1) {
            return ["<hr>\n", $index + 1];
        }

        return null;
    }

    /**
     * Consumes a blockquote, or returns null.
     *
     * @param list<string> $lines
     *
     * @return ?array{string, int}
     */
    public function quoteBlock(array $lines, int $index): ?array
    {
        if (preg_match('/^\s*>/', $lines[$index]) !== 1) {
            return null;
        }

        $content = [];
        while ($index < count($lines) && preg_match('/^\s*>\s?(.*)$/', $lines[$index], $match) === 1) {
            $content[] = $match[1];
            $index++;
        }

        $inner = $this->render(implode("\n", $content));

        return ['<blockquote>' . $inner . "</blockquote>\n", $index];
    }

    /**
     * Consumes an unordered or ordered list, or returns null.
     *
     * @param list<string> $lines
     *
     * @return ?array{string, int}
     */
    public function listBlock(array $lines, int $index): ?array
    {
        if (preg_match('/^(\s*)([-*+]|\d+\.)\s+/', $lines[$index], $match) !== 1 || strlen($match[1]) > 3) {
            return null;
        }

        $ordered = preg_match('/^\d+\.$/', $match[2]) === 1;
        $items = [];
        while ($index < count($lines) && preg_match('/^(\s*)([-*+]|\d+\.)\s+(.*)$/', $lines[$index], $item) === 1) {
            $depth = strlen($item[1]) >= 2 ? 1 : 0;
            $text = $item[3];
            $index++;
            while ($index < count($lines) && preg_match('/^\s{2,}\S/', $lines[$index]) === 1 && preg_match('/^(\s*)([-*+]|\d+\.)\s+/', $lines[$index]) !== 1) {
                $text .= ' ' . trim($lines[$index]);
                $index++;
            }

            $items[] = ['depth' => $depth, 'text' => $text];
        }

        return [$this->listHtml($items, $ordered), $index];
    }

    /**
     * Renders parsed list items with one nesting level.
     *
     * @param list<array{depth: int, text: string}> $items
     */
    public function listHtml(array $items, bool $ordered): string
    {
        $tag = $ordered ? 'ol' : 'ul';
        $html = '<' . $tag . '>';
        $open = false;
        foreach ($items as $position => $item) {
            if ($item['depth'] === 0) {
                if ($open) {
                    $html .= '</ul></li>';
                    $open = false;
                } elseif ($position > 0) {
                    $html .= '</li>';
                }

                $html .= '<li>' . $this->inline->render($item['text']);
            } else {
                if (!$open) {
                    $html .= '<ul>';
                    $open = true;
                }

                $html .= '<li>' . $this->inline->render($item['text']) . '</li>';
            }
        }

        if ($open) {
            $html .= '</ul></li>';
        } elseif ($items !== []) {
            $html .= '</li>';
        }

        return $html . '</' . $tag . ">\n";
    }

    /**
     * Consumes a pipe table, or returns null.
     *
     * @param list<string> $lines
     *
     * @return ?array{string, int}
     */
    public function tableBlock(array $lines, int $index): ?array
    {
        if (
            preg_match('/\|/', $lines[$index]) !== 1
            || !isset($lines[$index + 1])
            || preg_match('/^\s*\|?[\s:|-]+\|[\s:|-]*$/', $lines[$index + 1]) !== 1
        ) {
            return null;
        }

        $header = $this->tableCells($lines[$index]);
        $index += 2;
        $rows = [];
        while ($index < count($lines) && preg_match('/\|/', $lines[$index]) === 1 && trim($lines[$index]) !== '') {
            $rows[] = $this->tableCells($lines[$index]);
            $index++;
        }

        $html = '<div class="table-wrap"><table><thead><tr>';
        foreach ($header as $cell) {
            $html .= '<th>' . $this->inline->render($cell) . '</th>';
        }

        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>' . implode('', array_map(fn (string $cell): string => '<td>' . $this->inline->render($cell) . '</td>', $row)) . '</tr>';
        }

        return [$html . "</tbody></table></div>\n", $index];
    }

    /**
     * Splits one table line into trimmed cell texts.
     *
     * @return list<string>
     */
    public function tableCells(string $line): array
    {
        $cells = preg_split('/(?<!\\\\)\|/', trim(trim($line), '|'));

        return array_map(static fn (string $cell): string => str_replace('\\|', '|', trim($cell)), $cells === false ? [] : $cells);
    }

    /**
     * Consumes a paragraph running until the next blank line or block.
     *
     * @param list<string> $lines
     *
     * @return array{string, int}
     */
    public function paragraphBlock(array $lines, int $index): array
    {
        $content = [];
        while ($index < count($lines) && trim($lines[$index]) !== '') {
            if ($content !== [] && preg_match('/^(```|#{1,6}\s|\s*>|(\s*)([-*+]|\d+\.)\s)/', $lines[$index]) === 1) {
                break;
            }

            $content[] = trim($lines[$index]);
            $index++;
        }

        return ['<p>' . $this->inline->render(implode(' ', $content)) . "</p>\n", $index];
    }
}
