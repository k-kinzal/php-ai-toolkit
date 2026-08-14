<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Analysis;

use function count;

use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;

use function sprintf;

/**
 * Checks the recursive total file count limit of one directory subtree.
 */
final class TotalFileCountInspector
{
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

        $prefix = $listing->relativePath . '/';
        $total = count($listing->fileNames);
        foreach ($listings as $relativePath => $descendant) {
            if (str_starts_with($relativePath, $prefix)) {
                $total += count($descendant->fileNames);
            }
        }

        if ($total <= $rule->maxTotalFiles) {
            return [];
        }

        return [new Violation($listing->relativePath, 'max_total_files', $rule->path, $total, $rule->maxTotalFiles, sprintf('Directory "%s" contains %d files in total but the limit is %d. Restructure or split the subtree.', $listing->relativePath, $total, $rule->maxTotalFiles))];
    }
}
