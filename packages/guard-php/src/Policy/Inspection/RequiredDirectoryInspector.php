<?php

declare(strict_types=1);

namespace Guard\Policy\Inspection;

use Guard\Input\DirectoryListing;
use Guard\Input\Path as PathResolver;
use Guard\Policy\Definition\DirectoryRuleConfig;
use Guard\Policy\Diagnostic\DirectoryViolation;

/**
 * Checks that required subdirectory names exist directly in one directory.
 */
final class RequiredDirectoryInspector
{
    /**
     * Returns one violation for each missing required subdirectory.
     *
     * @return list<DirectoryViolation>
     */
    public function inspect(DirectoryRuleConfig $rule, DirectoryListing $listing): array
    {
        $violations = [];
        foreach ($rule->requireDirs ?? [] as $name) {
            if (!in_array($name, $listing->dirNames, true)) {
                $violations[] = new DirectoryViolation((new PathResolver())->child($listing->relativePath, $name), 'missing_required_dir', $rule->path, null, null, sprintf('Directory "%s" is missing required subdirectory "%s". Create it.', $listing->relativePath, $name));
            }
        }

        return $violations;
    }
}
