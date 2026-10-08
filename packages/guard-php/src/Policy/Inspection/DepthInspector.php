<?php

declare(strict_types=1);

namespace Guard\Policy\Inspection;

use Guard\Input\DirectoryListing;
use Guard\Input\Path as PathResolver;
use Guard\Policy\Definition\DirectoryRuleConfig;
use Guard\Policy\Diagnostic\DirectoryViolation;

use function sprintf;
use function strlen;
use function substr;
use function substr_count;

/**
 * Checks the nesting depth limit below one matched directory.
 *
 * The matched directory itself is depth zero and every descendant directory
 * that exceeds the limit produces its own violation.
 */
final class DepthInspector
{
    /** @readonly */
    private PathResolver $pathResolver;

    /**
     * Creates an inspector from path composition.
     */
    public function __construct(?PathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new PathResolver();
    }

    /**
     * Returns max_depth violations for descendants of the directory.
     *
     * @param array<string, DirectoryListing> $listings
     * @return list<DirectoryViolation>
     */
    public function inspect(DirectoryRuleConfig $rule, DirectoryListing $listing, array $listings): array
    {
        if ($rule->maxDepth === null) {
            return [];
        }

        $violations = [];
        $prefix = $this->pathResolver->descendantPrefix($listing->relativePath);
        foreach ($listings as $relativePath => $descendant) {
            if ($relativePath === $listing->relativePath || !str_starts_with($relativePath, $prefix)) {
                continue;
            }
            $depth = substr_count(substr($relativePath, strlen($prefix)), '/') + 1;
            if ($depth > $rule->maxDepth) {
                $violations[] = new DirectoryViolation($relativePath, 'max_depth', $rule->path, $depth, $rule->maxDepth, sprintf('Directory "%s" is nested %d levels below "%s" but the limit is %d. Flatten the directory structure.', $relativePath, $depth, $listing->relativePath, $rule->maxDepth));
            }
        }

        return $violations;
    }
}
