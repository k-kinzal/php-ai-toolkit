<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;

/**
 * Keeps explicitly declared generated paths out of the Git index.
 */
final class UntrackedFiles implements Policy
{
    /**
     * @param list<string> $paths generated file patterns relative to guard.yaml
     * @param list<string> $exclude intentionally versioned files within those patterns
     */
    public function __construct(private array $paths, private array $exclude = [])
    {
    }

    /**
     * @return array<string, Input>
     */
    public function inputs(Context $context): array
    {
        return ['tracked' => new Input(new Selection('git-tracked', $this->paths, $this->exclude))];
    }

    /**
     * Reports tracked artifacts without deleting files or changing the Git index.
     * @throws PolicyException when the declared input is unavailable
     */
    public function evaluate(InputSet $inputs, Context $context): Plan
    {
        $findings = [];
        foreach ($inputs->get('tracked')->files as $file) {
            $path = $file->file->relativePath;
            $findings[] = new Finding($path, 'git.tracked_generated_file', 'required', sprintf('Generated file "%s" is tracked by Git and matches the configured UntrackedFiles paths. Remove this path from the index with git rm --cached and add it to .gitignore, or explicitly exclude it from this policy if it is intentionally versioned.', $path));
        }
        return new Plan($findings, []);
    }
}
