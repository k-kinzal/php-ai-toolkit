<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function count;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function str_replace;

/**
 * Renders inline Markdown constructs inside one line of text.
 *
 * Code spans are protected before escaping so their contents are shown
 * verbatim; only code, emphasis, and links are supported by design.
 *
 * An image is reduced to its alt text: the generated site is meant to be
 * self-contained, so a README badge must not turn into a request to a
 * remote host, and the alt text carries the same information.
 *
 * A link to an absolute URL or to a page anchor becomes an anchor element,
 * and a relative path is offered to the link resolver, which turns a link
 * to a document of the same repository into a link to its rendered page.
 * Every remaining target has no counterpart in the generated site, so its
 * text is rendered plainly with the target kept in the title attribute
 * instead of being emitted as a broken link or, worse, as raw Markdown.
 */
final class MarkdownInline
{
    /** @readonly */
    private ?MarkdownLinks $links;

    /**
     * Creates an inline renderer, optionally resolving document links.
     */
    public function __construct(?MarkdownLinks $links = null)
    {
        $this->links = $links;
    }

    /**
     * Renders inline code, emphasis, and links of one text fragment.
     */
    public function render(string $text): string
    {
        $escaper = new HtmlText();
        $links = $this->links;
        $codes = [];
        $protected = preg_replace_callback('/`([^`]+)`/', static function (array $match) use (&$codes, $escaper): string {
            $codes[] = '<code>' . $escaper->e($match[1]) . '</code>';

            return "\x1A" . (count($codes) - 1) . "\x1A";
        }, $text) ?? $text;

        $html = $escaper->e($protected);
        $html = preg_replace('/(?<=^|\s)\*\*(\S(?:[^*]*\S)?)\*\*(?=$|[\s.,:;!?)])/', '<strong>$1</strong>', $html) ?? $html;
        $html = preg_replace('/(?<=^|\s)\*([^*\s][^*]*)\*(?=$|[\s.,:;!?)])/', '<em>$1</em>', $html) ?? $html;
        $html = preg_replace_callback('/!\[([^\]]*)\]\(((?:[^()\s]|\([^()\s]*\))+)\)/', static function (array $match): string {
            return $match[1] === ''
                ? ''
                : sprintf('<span class="md-target" title="%s">%s</span>', $match[2], $match[1]);
        }, $html) ?? $html;
        $html = preg_replace_callback('/\[([^\]]+)\]\(((?:[^()\s]|\([^()\s]*\))+)\)/', static function (array $match) use ($links, $escaper): string {
            if (preg_match('#^(https?://|\#)#', $match[2]) === 1) {
                return sprintf('<a href="%s">%s</a>', $match[2], $match[1]);
            }

            $href = $links !== null ? $links->resolve($match[2]) : null;
            if ($href !== null) {
                return sprintf('<a href="%s">%s</a>', $escaper->e($href), $match[1]);
            }

            return sprintf('<span class="md-target" title="%s">%s</span>', $match[2], $match[1]);
        }, $html) ?? $html;

        foreach ($codes as $position => $code) {
            $html = str_replace("\x1A" . $position . "\x1A", $code, $html);
        }

        return $html;
    }
}
