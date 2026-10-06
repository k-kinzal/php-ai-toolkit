<?php

declare(strict_types=1);

namespace Guard\Policy\Inspection;

use function count;

use Guard\Collect\DirectoryListing;
use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Reporting\DirectoryViolation;

use function sprintf;

/**
 * Checks the direct file and subdirectory count limits of one directory.
 */
final class ChildCountInspector
{
    /**
     * Returns max_files and max_dirs violations for the directory.
     *
     * @return list<DirectoryViolation>
     */
    public function inspect(DirectoryRuleConfig $rule, DirectoryListing $listing): array
    {
        $violations = [];
        $fileCount = count($listing->fileNames);
        if ($rule->maxFiles !== null && $fileCount > $rule->maxFiles) {
            $violations[] = new DirectoryViolation($listing->relativePath, 'max_files', $rule->path, $fileCount, $rule->maxFiles, sprintf('Directory "%s" contains %d files but the limit is %d. Move or merge files until at most %d remain.', $listing->relativePath, $fileCount, $rule->maxFiles, $rule->maxFiles));
        }

        $dirCount = count($listing->dirNames);
        if ($rule->maxDirs !== null && $dirCount > $rule->maxDirs) {
            $violations[] = new DirectoryViolation($listing->relativePath, 'max_dirs', $rule->path, $dirCount, $rule->maxDirs, sprintf('Directory "%s" contains %d subdirectories but the limit is %d. Merge or flatten subdirectories.', $listing->relativePath, $dirCount, $rule->maxDirs));
        }

        return $violations;
    }
}
