<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Detects suppression comments handled by ForbiddenCommentRule.
 */
final class ForbiddenCommentPattern
{
    private const PHPSTAN_IGNORE_PATTERN = '/@phpstan-ignore(?:-line|-next-line)?\b/';
    private const INFECTION_IGNORE_ALL_PATTERN = '/@infection-ignore-all\b/';

    /**
     * Reports whether the text contains a PHPStan ignore directive.
     */
    public function isPhpstanIgnore(string $text): bool
    {
        return preg_match(self::PHPSTAN_IGNORE_PATTERN, $text) === 1;
    }

    /**
     * Reports whether the text contains an Infection ignore-all directive.
     */
    public function isInfectionIgnoreAll(string $text): bool
    {
        return preg_match(self::INFECTION_IGNORE_ALL_PATTERN, $text) === 1;
    }

    /**
     * Reports whether the comment is already handled by ForbiddenCommentRule.
     */
    public function isHandled(string $text): bool
    {
        return $this->isPhpstanIgnore($text) || $this->isInfectionIgnoreAll($text);
    }

    /**
     * Returns the line PHPStan associates with an ignore comment.
     */
    public function reportedLine(string $text, int $line): int
    {
        if (str_contains($text, '@phpstan-ignore-line')) {
            return $line + 1;
        }

        return $line;
    }
}
