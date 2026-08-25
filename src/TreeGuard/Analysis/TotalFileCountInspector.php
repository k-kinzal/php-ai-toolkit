<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Analysis;

use function count;
use function sprintf;

use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;
use Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver;

/**
 * Checks the recursive total file count limit of one directory subtree.
 */
final class TotalFileCountInspector
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
     * Returns max_total_files violations for the directory subtree.
     *
     * @param array<string, DirectoryListing> $listings
     * @return list<Violation>
     */
    public function inspect(RuleConfig $rule, DirectoryListing $listing, array $listings): array
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

        return [new Violation($listing->relativePath, 'max_total_files', $rule->path, $total, $rule->maxTotalFiles, sprintf('Directory "%s" contains %d files in total but the limit is %d. Restructure or split the subtree.', $listing->relativePath, $total, $rule->maxTotalFiles))];
    }
}
