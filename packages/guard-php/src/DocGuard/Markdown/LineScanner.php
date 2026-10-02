<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Markdown;

use function ltrim;
use function trim;

/**
 * Advances the parser state by one line and returns the heading that line completes.
 */
final class LineScanner
{
    /** @readonly */
    private FenceMatcher $fenceMatcher;

    /** @readonly */
    private HtmlBlockMatcher $htmlBlockMatcher;

    /** @readonly */
    private LineIndentation $indentation;

    /** @readonly */
    private BlockLineScanner $blockScanner;

    /**
     * Creates a line scanner from fence, HTML block, indentation, and block handling.
     */
    public function __construct(
        ?FenceMatcher $fenceMatcher = null,
        ?HtmlBlockMatcher $htmlBlockMatcher = null,
        ?LineIndentation $indentation = null,
        ?BlockLineScanner $blockScanner = null,
    ) {
        $this->fenceMatcher = $fenceMatcher ?? new FenceMatcher();
        $this->htmlBlockMatcher = $htmlBlockMatcher ?? new HtmlBlockMatcher();
        $this->indentation = $indentation ?? new LineIndentation();
        $this->blockScanner = $blockScanner ?? new BlockLineScanner();
    }

    /**
     * Scans one line; lines inside fenced code and HTML blocks never produce headings.
     */
    public function scan(ParserState $state, string $line, int $lineNumber): ?Heading
    {
        if ($state->fence !== null) {
            if ($this->fenceMatcher->closes($state->fence, $line)) {
                $state->fence = null;
            }

            return null;
        }

        $blank = trim($line) === '';
        if ($state->htmlEnd !== null) {
            if ($state->htmlEnd === HtmlBlockMatcher::BLANK_LINE ? $blank : $this->htmlBlockMatcher->ends($state->htmlEnd, $line)) {
                $state->htmlEnd = null;
            }
            $state->previousBlank = $blank;

            return null;
        }

        if ($blank) {
            $state->paragraph = [];
            $state->previousBlank = true;

            return null;
        }

        $indent = $this->indentation->width($line);
        $heading = null;
        if ($indent >= 4) {
            $this->blockScanner->scanIndented($state, $line);
        } else {
            $heading = $this->blockScanner->scan($state, ltrim($line, " \t"), $indent, $lineNumber);
        }
        $state->previousBlank = false;

        return $heading;
    }
}
