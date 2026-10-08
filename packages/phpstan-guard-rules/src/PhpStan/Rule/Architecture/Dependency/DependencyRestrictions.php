<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture\Dependency;

/**
 * Applies directed file dependency restrictions to resolved targets.
 */
final class DependencyRestrictions
{
    /**
     * @var list<array{from: list<string>, excludeFrom?: list<string>, to: list<string>}>
     */
    public const DEFAULTS = [
        ['from' => ['**'], 'excludeFrom' => ['example/**', 'examples/**'], 'to' => ['example/**', 'examples/**']],
    ];

    /**
     * @param list<array{from: list<string>, excludeFrom?: list<string>, to: list<string>}> $restrictions
     */
    public function __construct(
        private DependencyPath $paths,
        private array $restrictions = self::DEFAULTS,
    ) {
    }

    /**
     * Reports whether any policy checks dependencies originating in this file.
     */
    public function checks(string $source): bool
    {
        foreach ($this->restrictions as $restriction) {
            if ($this->paths->matches($source, $restriction['from'])
                && !$this->paths->matches($source, $restriction['excludeFrom'] ?? [])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the first violated target pattern, avoiding overlapping-policy duplicates.
     */
    public function violation(string $source, string $target): ?string
    {
        foreach ($this->restrictions as $restriction) {
            if (!$this->paths->matches($source, $restriction['from'])
                || $this->paths->matches($source, $restriction['excludeFrom'] ?? [])) {
                continue;
            }
            foreach ($restriction['to'] as $pattern) {
                if ($this->paths->matches($target, [$pattern])) {
                    return $pattern;
                }
            }
        }

        return null;
    }
}
