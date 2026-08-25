<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Analysis;

use function count;
use function sprintf;

use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;

/**
 * Checks the direct file and subdirectory count limits of one directory.
 */
final class ChildCountInspector
{
    /**
     * Returns max_files and max_dirs violations for the directory.
     *
     * @return list<Violation>
     */
    public function inspect(RuleConfig $rule, DirectoryListing $listing): array
    {
        $violations = [];
        $fileCount = count($listing->fileNames);
        if ($rule->maxFiles !== null && $fileCount > $rule->maxFiles) {
            $violations[] = new Violation($listing->relativePath, 'max_files', $rule->path, $fileCount, $rule->maxFiles, sprintf('Directory "%s" contains %d files but the limit is %d. Move or merge files until at most %d remain.', $listing->relativePath, $fileCount, $rule->maxFiles, $rule->maxFiles));
        }

        $dirCount = count($listing->dirNames);
        if ($rule->maxDirs !== null && $dirCount > $rule->maxDirs) {
            $violations[] = new Violation($listing->relativePath, 'max_dirs', $rule->path, $dirCount, $rule->maxDirs, sprintf('Directory "%s" contains %d subdirectories but the limit is %d. Merge or flatten subdirectories.', $listing->relativePath, $dirCount, $rule->maxDirs));
        }

        return $violations;
    }
}
