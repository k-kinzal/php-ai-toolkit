<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Analysis;

use function in_array;

use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;

use function sprintf;

/**
 * Checks that required file names exist directly in one directory.
 */
final class RequiredFileInspector
{
    /**
     * Returns missing_required_file violations for the directory.
     *
     * @return list<Violation>
     */
    public function inspect(RuleConfig $rule, DirectoryListing $listing): array
    {
        $violations = [];
        foreach ($rule->require ?? [] as $name) {
            if (!in_array($name, $listing->fileNames, true)) {
                $violations[] = new Violation($listing->relativePath . '/' . $name, 'missing_required_file', $rule->path, null, null, sprintf('Directory "%s" is missing required file "%s". Create it.', $listing->relativePath, $name));
            }
        }

        return $violations;
    }
}
