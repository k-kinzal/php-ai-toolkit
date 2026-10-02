<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render;

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
     * Span class of every token id that highlights on its id alone.
     *
     * Source listings walk every token of every documented file, so the
     * class of a token is looked up once in this table instead of being
     * searched for through a chain of token comparisons.
     *
     * @var array<int, string>
     */
    private const TOKEN_CLASSES = [
        T_COMMENT => 'tok-com',
        T_DOC_COMMENT => 'tok-com',
        T_CONSTANT_ENCAPSED_STRING => 'tok-str',
        T_ENCAPSED_AND_WHITESPACE => 'tok-str',
        T_LNUMBER => 'tok-num',
        T_DNUMBER => 'tok-num',
        T_VARIABLE => 'tok-var',
        T_STRING => 'tok-id',
        T_NAME_QUALIFIED => 'tok-id',
        T_NAME_FULLY_QUALIFIED => 'tok-id',
        T_NAME_RELATIVE => 'tok-id',
    ];

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
        $id = $token->id;
        if (isset(self::TOKEN_CLASSES[$id])) {
            return self::TOKEN_CLASSES[$id];
        }

        if ($id < 256 || $id === T_INLINE_HTML) {
            return null;
        }

        return ctype_alpha(str_starts_with($token->text, '?') ? substr($token->text, 1) : $token->text)
            ? 'tok-kw'
            : null;
    }
}
