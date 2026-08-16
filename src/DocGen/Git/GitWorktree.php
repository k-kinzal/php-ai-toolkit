<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Git;

use function file_exists;
use function is_dir;

use PhpAiToolkit\DocGen\DocGenException;

use function symlink;

/**
 * Checks a commit out into a throwaway worktree next to the project.
 *
 * A worktree is used instead of an archive because an archive applies the
 * export-ignore rules of the repository, which would hide exactly the tests
 * and documents the site is meant to describe.
 */
final class GitWorktree
{
    /**
     * Prefix of every temporary checkout directory.
     */
    public const PREFIX = 'docgen-diff-';

    /** @readonly */
    private GitCommandRunner $runner;

    /** @readonly */
    private TempDirectory $temp;

    /**
     * Creates a worktree checkout from its git and filesystem collaborators.
     */
    public function __construct(?GitCommandRunner $runner = null, ?TempDirectory $temp = null)
    {
        $this->runner = $runner ?? new GitCommandRunner();
        $this->temp = $temp ?? new TempDirectory();
    }

    /**
     * Checks one commit out and returns the checkout directory.
     *
     * @throws DocGenException when the checkout fails
     */
    public function create(string $repositoryRoot, string $commit): string
    {
        $path = $this->temp->create(self::PREFIX);
        $this->runner->run(['worktree', 'add', '--detach', '--quiet', $path, $commit], $repositoryRoot);

        return $path;
    }

    /**
     * Links the installed dependencies of the project into a checkout.
     *
     * An older revision was never installed, so without the link every
     * documented dependency of the project would read as newly added.
     */
    public function linkVendor(string $repositoryRoot, string $path): void
    {
        $vendor = $repositoryRoot . '/vendor';
        $target = $path . '/vendor';
        if (is_dir($vendor) && !file_exists($target)) {
            @symlink($vendor, $target);
        }
    }

    /**
     * Removes one checkout and its registration in the repository.
     *
     * The vendor link is unlinked first so no removal step can ever descend
     * into the installed dependencies of the working tree.
     */
    public function remove(string $repositoryRoot, string $path): void
    {
        $this->temp->remove($path . '/vendor');
        $this->runner->execute(['worktree', 'remove', '--force', $path], $repositoryRoot);
        $this->temp->remove($path);
        $this->runner->execute(['worktree', 'prune'], $repositoryRoot);
    }
}
