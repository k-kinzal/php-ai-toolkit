<?php

declare(strict_types=1);

namespace Guard\Collect\Markdown\Parsing;

/**
 * Mutable block context carried from one Markdown line to the next.
 */
final class ParserState
{
    /**
     * The open fenced code block, or null outside a fence.
     */
    public ?Fence $fence = null;

    /**
     * The end condition of the open HTML block, or null outside an HTML block.
     */
    public ?string $htmlEnd = null;

    /**
     * The lines of the open top-level paragraph, which a setext underline turns into a heading.
     *
     * @var list<string>
     */
    public array $paragraph = [];

    /**
     * The 1-based line on which the open paragraph started.
     */
    public int $paragraphLine = 0;

    /**
     * Whether the preceding lines belong to a list item, whose paragraphs cannot become setext headings.
     */
    public bool $listContext = false;

    /**
     * Whether the previous line was blank.
     */
    public bool $previousBlank = true;

    /**
     * Closes the open paragraph because another block starts, and leaves list context at column zero.
     */
    public function interrupt(int $indent): void
    {
        $this->paragraph = [];
        if ($indent === 0) {
            $this->listContext = false;
        }
    }
}
