<?php

declare(strict_types=1);

namespace Guard\Policy\Tree;

use Guard\Collect\Tree\Filesystem\DirectoryListing;
use Guard\Collect\Tree\Filesystem\PathResolver;
use Guard\Config\Tree\RuleConfig;

use function in_array;
use function sprintf;

/**
 * Checks that required file names exist directly in one directory.
 */
final class RequiredFileInspector
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
     * Returns missing_required_file violations for the directory.
     *
     * @return list<Violation>
     */
    public function inspect(RuleConfig $rule, DirectoryListing $listing): array
    {
        $violations = [];
        foreach ($rule->require ?? [] as $name) {
            if (!in_array($name, $listing->fileNames, true)) {
                $violations[] = new Violation($this->pathResolver->child($listing->relativePath, $name), 'missing_required_file', $rule->path, null, null, sprintf('Directory "%s" is missing required file "%s". Create it.', $listing->relativePath, $name));
            }
        }

        return $violations;
    }
}
