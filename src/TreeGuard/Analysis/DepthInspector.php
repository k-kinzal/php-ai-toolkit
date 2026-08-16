<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Analysis;

use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver;

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
    private TreeGuardPathResolver $pathResolver;

    /**
     * Creates an inspector from path composition.
     */
    public function __construct(?TreeGuardPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new TreeGuardPathResolver();
    }

    /**
     * Returns max_depth violations for descendants of the directory.
     *
     * @param array<string, DirectoryListing> $listings
     * @return list<Violation>
     */
    public function inspect(RuleConfig $rule, DirectoryListing $listing, array $listings): array
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
                $violations[] = new Violation($relativePath, 'max_depth', $rule->path, $depth, $rule->maxDepth, sprintf('Directory "%s" is nested %d levels below "%s" but the limit is %d. Flatten the directory structure.', $relativePath, $depth, $listing->relativePath, $rule->maxDepth));
            }
        }

        return $violations;
    }
}
