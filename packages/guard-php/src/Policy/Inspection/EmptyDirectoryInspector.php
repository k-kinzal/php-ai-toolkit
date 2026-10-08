<?php

declare(strict_types=1);

namespace Guard\Policy\Inspection;

use Guard\Input\DirectoryListing;
use Guard\Policy\Definition\DirectoryRuleConfig;
use Guard\Policy\Diagnostic\DirectoryViolation;

use function sprintf;

/**
 * Checks that one directory is not empty after exclusions are applied.
 */
final class EmptyDirectoryInspector
{
    /**
     * Returns empty_directory violations for the directory.
     *
     * @return list<DirectoryViolation>
     */
    public function inspect(DirectoryRuleConfig $rule, DirectoryListing $listing): array
    {
        if (!$rule->forbidEmpty || $listing->fileNames !== [] || $listing->dirNames !== []) {
            return [];
        }

        return [new DirectoryViolation($listing->relativePath, 'empty_directory', $rule->path, null, null, sprintf('Directory "%s" is empty. Delete it or add its intended contents.', $listing->relativePath))];
    }
}
