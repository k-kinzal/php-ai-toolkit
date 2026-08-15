<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

use function htmlspecialchars;

/**
 * Escapes text for safe embedding into generated HTML.
 */
final class HtmlText
{
    /**
     * Escapes one text fragment for element and attribute contexts.
     */
    public function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
