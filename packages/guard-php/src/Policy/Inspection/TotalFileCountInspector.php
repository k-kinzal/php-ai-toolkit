<?php

declare(strict_types=1);

namespace Guard\Policy\Inspection;

use function count;

use Guard\Collect\DirectoryListing;
use Guard\Collect\Filesystem\Path as PathResolver;
use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Reporting\DirectoryViolation;

use function sprintf;

/**
 * Checks the recursive total file count limit of one directory subtree.
 */
final class TotalFileCountInspector
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
     * Returns max_total_files violations for the directory subtree.
     *
     * @param array<string, DirectoryListing> $listings
     * @return list<DirectoryViolation>
     */
    public function inspect(DirectoryRuleConfig $rule, DirectoryListing $listing, array $listings): array
    {
        if ($rule->maxTotalFiles === null) {
            return [];
        }

        $prefix = $this->pathResolver->descendantPrefix($listing->relativePath);
        $total = count($listing->fileNames);
        foreach ($listings as $relativePath => $descendant) {
            if ($relativePath !== $listing->relativePath && str_starts_with($relativePath, $prefix)) {
                $total += count($descendant->fileNames);
            }
        }

        if ($total <= $rule->maxTotalFiles) {
            return [];
        }

        return [new DirectoryViolation($listing->relativePath, 'max_total_files', $rule->path, $total, $rule->maxTotalFiles, sprintf('Directory "%s" contains %d files in total but the limit is %d. Restructure or split the subtree.', $listing->relativePath, $total, $rule->maxTotalFiles))];
    }
}
