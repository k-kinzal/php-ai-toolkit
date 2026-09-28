<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Markdown;

use function implode;
use function ltrim;

/**
 * Classifies one non-blank line outside fenced code and HTML blocks.
 */
final class BlockLineScanner
{
    /** @readonly */
    private FenceMatcher $fenceMatcher;

    /** @readonly */
    private AtxHeadingMatcher $atxMatcher;

    /** @readonly */
    private HtmlBlockMatcher $htmlBlockMatcher;

    /** @readonly */
    private SetextUnderlineMatcher $setextMatcher;

    /** @readonly */
    private BlockMarkerMatcher $markerMatcher;

    /** @readonly */
    private HeadingTextNormalizer $normalizer;

    /**
     * Creates a scanner from the individual block matchers.
     */
    public function __construct(
        ?FenceMatcher $fenceMatcher = null,
        ?AtxHeadingMatcher $atxMatcher = null,
        ?HtmlBlockMatcher $htmlBlockMatcher = null,
        ?SetextUnderlineMatcher $setextMatcher = null,
        ?BlockMarkerMatcher $markerMatcher = null,
        ?HeadingTextNormalizer $normalizer = null,
    ) {
        $this->fenceMatcher = $fenceMatcher ?? new FenceMatcher();
        $this->atxMatcher = $atxMatcher ?? new AtxHeadingMatcher();
        $this->htmlBlockMatcher = $htmlBlockMatcher ?? new HtmlBlockMatcher();
        $this->setextMatcher = $setextMatcher ?? new SetextUnderlineMatcher();
        $this->markerMatcher = $markerMatcher ?? new BlockMarkerMatcher();
        $this->normalizer = $normalizer ?? new HeadingTextNormalizer();
    }

    /**
     * Scans a line indented by at most three columns, given without its indentation.
     */
    public function scan(ParserState $state, string $content, int $indent, int $lineNumber): ?Heading
    {
        $fence = $this->fenceMatcher->open($content);
        if ($fence !== null) {
            $state->interrupt($indent);
            $state->fence = $fence;

            return null;
        }

        $heading = $this->atxMatcher->match($content, $lineNumber);
        if ($heading !== null) {
            $state->interrupt($indent);

            return $heading;
        }

        $htmlEnd = $this->htmlBlockMatcher->start($content, $state->paragraph !== []);
        if ($htmlEnd !== null) {
            $state->interrupt($indent);
            $state->htmlEnd = $this->htmlBlockMatcher->ends($htmlEnd, $content) ? null : $htmlEnd;

            return null;
        }

        $underline = $this->setextMatcher->level($content);
        if ($underline !== null && $state->paragraph !== []) {
            $heading = new Heading($underline, $this->normalizer->normalize(implode(' ', $state->paragraph)), $state->paragraphLine);
            $state->paragraph = [];

            return $heading;
        }

        if ($this->markerMatcher->isThematicBreak($content) || $this->markerMatcher->isBlockQuote($content)) {
            $state->interrupt($indent);

            return null;
        }

        if ($this->markerMatcher->isListItem($content)) {
            $state->paragraph = [];
            $state->listContext = true;

            return null;
        }

        $this->scanParagraph($state, $content, $indent, $lineNumber);

        return null;
    }

    /**
     * Records a paragraph line unless it continues a list item.
     */
    public function scanParagraph(ParserState $state, string $content, int $indent, int $lineNumber): void
    {
        if ($state->listContext) {
            if (!$state->previousBlank || $indent > 0) {
                return;
            }
            $state->listContext = false;
        }

        if ($state->paragraph === []) {
            $state->paragraphLine = $lineNumber;
        }
        $state->paragraph[] = $content;
    }

    /**
     * Scans a line indented by four or more columns, which is never a heading.
     *
     * It continues an open paragraph, and inside a list item it may open a fenced code block.
     */
    public function scanIndented(ParserState $state, string $line): void
    {
        if ($state->paragraph !== []) {
            $state->paragraph[] = ltrim($line, " \t");

            return;
        }

        if ($state->listContext) {
            $state->fence = $this->fenceMatcher->open(ltrim($line, " \t"));
        }
    }
}
