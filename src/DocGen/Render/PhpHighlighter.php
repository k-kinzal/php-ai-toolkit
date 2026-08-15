<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function ctype_alpha;
use function explode;
use function implode;

use PhpToken;

use function str_starts_with;
use function strpos;
use function substr;

use const T_COMMENT;
use const T_CONSTANT_ENCAPSED_STRING;
use const T_DNUMBER;
use const T_DOC_COMMENT;
use const T_ENCAPSED_AND_WHITESPACE;
use const T_INLINE_HTML;
use const T_LNUMBER;
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_NAME_RELATIVE;
use const T_STRING;
use const T_VARIABLE;

/**
 * Server-side PHP syntax highlighter based on the tokenizer.
 *
 * Tokens never span the emitted line breaks, so callers can split the
 * result on newlines to build numbered source listings.
 */
final class PhpHighlighter
{
    /**
     * Highlights a full PHP source text into span-wrapped HTML.
     */
    public function highlight(string $code): string
    {
        $html = '';
        $escaper = new HtmlText();
        foreach (PhpToken::tokenize($code) as $token) {
            $class = $this->classFor($token);
            $parts = [];
            foreach (explode("\n", $token->text) as $part) {
                $escaped = $escaper->e($part);
                $parts[] = $class !== null && $part !== '' ? '<span class="' . $class . '">' . $escaped . '</span>' : $escaped;
            }

            $html .= implode("\n", $parts);
        }

        return $html;
    }

    /**
     * Highlights a snippet that has no opening PHP tag.
     */
    public function highlightSnippet(string $code): string
    {
        $html = $this->highlight("<?php\n" . $code);
        $break = strpos($html, "\n");

        return $break === false ? $html : substr($html, $break + 1);
    }

    /**
     * Returns the span class of one token, or null for plain text.
     */
    public function classFor(PhpToken $token): ?string
    {
        if ($token->is([T_COMMENT, T_DOC_COMMENT])) {
            return 'tok-com';
        }

        if ($token->is([T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE])) {
            return 'tok-str';
        }

        if ($token->is([T_LNUMBER, T_DNUMBER])) {
            return 'tok-num';
        }

        if ($token->is(T_VARIABLE)) {
            return 'tok-var';
        }

        if ($token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE])) {
            return 'tok-id';
        }

        if ($token->is(T_INLINE_HTML)) {
            return null;
        }

        if ($token->id >= 256 && ctype_alpha(str_starts_with($token->text, '?') ? substr($token->text, 1) : $token->text)) {
            return 'tok-kw';
        }

        return null;
    }
}
