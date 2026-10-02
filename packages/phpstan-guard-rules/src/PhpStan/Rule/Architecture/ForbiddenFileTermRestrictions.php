<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture;

use function strtolower;

use Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher;

/**
 * Holds normalized path-specific forbidden-term policies.
 */
final class ForbiddenFileTermRestrictions
{
    /**
     * @var list<array{path: string, terms: list<string>, matcher: RulePathMatcher}>
     */
    private array $restrictions = [];

    /**
     * @param array<string, list<string>> $forbiddenTermsByPath path patterns mapped to literal forbidden terms
     */
    public function __construct(array $forbiddenTermsByPath = [])
    {
        foreach ($forbiddenTermsByPath as $path => $terms) {
            $terms = $this->uniqueTerms($terms);
            if ($path === '' || $terms === []) {
                continue;
            }

            $this->restrictions[] = [
                'path' => $path,
                'terms' => $terms,
                'matcher' => new RulePathMatcher([$path]),
            ];
        }
    }

    /**
     * Returns every restriction applying to one analyzed file.
     *
     * @return list<array{path: string, terms: list<string>}>
     */
    public function matching(string $filePath): array
    {
        $matching = [];
        foreach ($this->restrictions as $restriction) {
            if (!$restriction['matcher']->matches($filePath)) {
                continue;
            }

            $matching[] = [
                'path' => $restriction['path'],
                'terms' => $restriction['terms'],
            ];
        }

        return $matching;
    }

    /**
     * Removes empty and case-insensitive duplicate terms while preserving policy order.
     *
     * @param list<string> $terms
     * @return list<string>
     */
    public function uniqueTerms(array $terms): array
    {
        $unique = [];
        $seen = [];
        foreach ($terms as $term) {
            $key = strtolower($term);
            if ($term === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $term;
        }

        return $unique;
    }
}
