<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown\Badge;

use Guard\Structure\Markdown\Heading;
use Guard\Structure\Subject;

/**
 * The badges directly below a document's level-1 title.
 *
 * @property-read ?Heading $title
 * @property-read list<Badge> $badges
 * @property-read ?int $earlyLine
 */
final class BadgeBlock implements Subject
{
    /**
     * @param ?Heading $title the first level-1 heading, or null when the document has none
     * @param list<Badge> $badges the badges of the first block after the title
     * @param ?int $earlyLine the first line before the title that holds badges
     */
    public function __construct(
        /** @readonly */
        private ?Heading $title,
        /** @readonly */
        private array $badges,
        /** @readonly */
        private ?int $earlyLine,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'title' => $this->title,
            'badges' => $this->badges,
            'earlyLine' => $this->earlyLine,
            default => null,
        };
    }
}
