<?php

declare(strict_types=1);

namespace Guard\Collect\Markdown\Parsing;

use function count;

/**
 * Extracts the document headings from Markdown source.
 *
 * Only the block structure that decides whether a line is a heading is
 * parsed: ATX and setext headings, fenced code blocks with backticks and
 * tildes, HTML blocks, list items, block quotes, and YAML front matter.
 * Inline content is kept verbatim in the heading text.
 */
final class HeadingParser
{
    /** @readonly */
    private MarkdownLineSplitter $splitter;

    /** @readonly */
    private LineScanner $lineScanner;

    /**
     * Creates a parser from line splitting and line scanning.
     */
    public function __construct(?MarkdownLineSplitter $splitter = null, ?LineScanner $lineScanner = null)
    {
        $this->splitter = $splitter ?? new MarkdownLineSplitter();
        $this->lineScanner = $lineScanner ?? new LineScanner();
    }

    /**
     * Returns the headings of a Markdown document in source order.
     *
     * @return list<Heading>
     */
    public function parse(string $markdown): array
    {
        $lines = $this->splitter->split($markdown);
        $state = new ParserState();
        $headings = [];
        $count = count($lines);

        for ($index = $this->splitter->frontMatterLength($lines); $index < $count; $index++) {
            $heading = $this->lineScanner->scan($state, $lines[$index], $index + 1);
            if ($heading !== null) {
                $headings[] = $heading;
            }
        }

        return $headings;
    }
}
