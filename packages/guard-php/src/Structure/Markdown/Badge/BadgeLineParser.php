<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown\Badge;

use function preg_match;
use function preg_match_all;

use const PREG_SET_ORDER;

/**
 * Recognizes lines that consist of nothing but linked badge images.
 */
final class BadgeLineParser
{
    private const BADGE = '\[!\[([^\]]*)\]\(\s*<?([^)\s>]+)>?(?:\s+"[^"]*")?\s*\)\]\(\s*<?([^)\s>]+)>?(?:\s+"[^"]*")?\s*\)';

    /**
     * Returns the badges of a badge-only line, or null when the line holds anything else.
     *
     * @return ?list<Badge>
     */
    public function parse(string $line, int $lineNumber): ?array
    {
        if (preg_match('/^ {0,3}(?:' . self::BADGE . '[ \t]*)+$/', $line) !== 1) {
            return null;
        }
        preg_match_all('/' . self::BADGE . '/', $line, $matches, PREG_SET_ORDER);
        $badges = [];
        foreach ($matches as $match) {
            $badges[] = new Badge($match[1], $match[2], $match[3], $lineNumber);
        }

        return $badges;
    }
}
