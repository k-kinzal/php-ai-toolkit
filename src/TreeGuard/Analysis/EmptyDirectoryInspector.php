<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Analysis;

use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;

use function sprintf;

/**
 * Checks that one directory is not empty after exclusions are applied.
 */
final class EmptyDirectoryInspector
{
    /**
     * Returns empty_directory violations for the directory.
     *
     * @return list<Violation>
     */
    public function inspect(RuleConfig $rule, DirectoryListing $listing): array
    {
        if (!$rule->forbidEmpty || $listing->fileNames !== [] || $listing->dirNames !== []) {
            return [];
        }

        return [new Violation($listing->relativePath, 'empty_directory', $rule->path, null, null, sprintf('Directory "%s" is empty. Delete it or add its intended contents.', $listing->relativePath))];
    }
}
