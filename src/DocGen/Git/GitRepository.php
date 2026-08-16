<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Git;

use PhpAiToolkit\DocGen\DocGenException;

use function sprintf;

/**
 * Answers the repository questions a diff run asks before checking out.
 */
final class GitRepository
{
    /** @readonly */
    private GitCommandRunner $runner;

    /**
     * Creates a repository reader from its command runner.
     */
    public function __construct(?GitCommandRunner $runner = null)
    {
        $this->runner = $runner ?? new GitCommandRunner();
    }

    /**
     * Returns the working tree root that contains one directory.
     *
     * @throws DocGenException when the directory is outside a git working tree
     */
    public function root(string $directory): string
    {
        $result = $this->runner->execute(['rev-parse', '--show-toplevel'], $directory);
        if ($result['status'] !== 0 || $result['output'] === '') {
            throw new DocGenException(sprintf(
                'Not a git working tree: %s. Diff mode compares two revisions, so it needs the documented project to live in a git repository.',
                $directory,
            ));
        }

        return $result['output'];
    }

    /**
     * Resolves one revision to the commit it names.
     *
     * @throws DocGenException when the revision does not name a commit
     */
    public function commit(string $repositoryRoot, string $revision): string
    {
        $result = $this->runner->execute(['rev-parse', '--verify', '--quiet', $revision . '^{commit}'], $repositoryRoot);
        if ($result['status'] !== 0 || $result['output'] === '') {
            throw new DocGenException(sprintf(
                'Unknown git revision: %s. Use a commit, branch, or tag that exists in %s.',
                $revision,
                $repositoryRoot,
            ));
        }

        return $result['output'];
    }

    /**
     * Returns the short label of one revision for the generated pages.
     *
     * The revision as the caller wrote it is kept when git cannot abbreviate
     * it, so a label never becomes less recognizable than the input.
     */
    public function label(string $repositoryRoot, string $revision): string
    {
        $result = $this->runner->execute(['rev-parse', '--short', $revision], $repositoryRoot);

        return $result['status'] === 0 && $result['output'] !== '' ? $result['output'] : $revision;
    }
}
