<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Markdown;

use function preg_match;

/**
 * Recognizes CommonMark HTML blocks, whose lines are raw HTML and never headings.
 *
 * An end condition is either a regular expression that ends the block on the
 * line it matches, or an empty string for blocks that end at a blank line.
 */
final class HtmlBlockMatcher
{
    /**
     * The end condition of HTML blocks that end at the next blank line.
     */
    public const BLANK_LINE = '';

    /** @var array<string, string> */
    private const TERMINATED_STARTS = [
        '/^<(?:script|pre|style|textarea)(?:[ \t>]|$)/i' => '#</(?:script|pre|style|textarea)>#i',
        '/^<!--/' => '/-->/',
        '/^<\?/' => '/\?>/',
        '/^<!\[CDATA\[/' => '/\]\]>/',
        '/^<![A-Za-z]/' => '/>/',
    ];

    private const BLOCK_TAG_START = '#^</?(?:address|article|aside|base|basefont|blockquote|body|caption|center|col|colgroup|dd|details|dialog|dir|div|dl|dt|fieldset|figcaption|figure|footer|form|frame|frameset|h[1-6]|head|header|hr|html|iframe|legend|li|link|main|menu|menuitem|nav|noframes|ol|optgroup|option|p|param|search|section|summary|table|tbody|td|tfoot|th|thead|title|tr|track|ul)(?:[ \t]|/?>|$)#i';

    private const COMPLETE_TAG_LINE = '#^(?:<[A-Za-z][A-Za-z0-9-]*(?:[ \t]+[A-Za-z_:][A-Za-z0-9_.:-]*(?:[ \t]*=[ \t]*(?:"[^"]*"|\'[^\']*\'|[^ \t"\'=<>`]+))?)*[ \t]*/?>|</[A-Za-z][A-Za-z0-9-]*[ \t]*>)[ \t]*$#';

    /**
     * Returns the end condition of an HTML block started by a line whose indentation is removed, or null.
     *
     * A line that is only a complete tag of an unknown element cannot interrupt a paragraph.
     */
    public function start(string $content, bool $paragraphOpen): ?string
    {
        foreach (self::TERMINATED_STARTS as $start => $end) {
            if (preg_match($start, $content) === 1) {
                return $end;
            }
        }

        if (preg_match(self::BLOCK_TAG_START, $content) === 1) {
            return self::BLANK_LINE;
        }

        if (!$paragraphOpen && preg_match(self::COMPLETE_TAG_LINE, $content) === 1) {
            return self::BLANK_LINE;
        }

        return null;
    }

    /**
     * Returns whether a line satisfies a regular-expression end condition.
     */
    public function ends(string $condition, string $line): bool
    {
        return $condition !== self::BLANK_LINE && preg_match($condition, $line) === 1;
    }
}
