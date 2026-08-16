<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\ProjectAnalyzer;
use PhpAiToolkit\DocGen\Cache\ParseCache;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Git\GitRepository;
use PhpAiToolkit\DocGen\Git\GitWorktree;
use PhpAiToolkit\DocGen\Git\RevisionRange;

/**
 * Opens, analyzes, and closes the two revisions a diff site compares.
 *
 * A session owns its checkouts: whatever happens while it is open, the
 * temporary worktrees are removed again by closing it.
 */
final class DiffWorkspace
{
    /**
     * Label of a head revision that is the working tree on disk.
     */
    public const WORKING_TREE = 'working tree';

    /** @readonly */
    private GitRepository $repository;

    /** @readonly */
    private GitWorktree $worktree;

    /** @readonly */
    private ProjectAnalyzer $analyzer;

    /** @readonly */
    private ProjectDiffer $differ;

    /** @readonly */
    private DocGenPathResolver $pathResolver;

    /**
     * Creates a diff workspace from its git and analysis collaborators.
     */
    public function __construct(
        ?GitRepository $repository = null,
        ?GitWorktree $worktree = null,
        ?ProjectAnalyzer $analyzer = null,
        ?ProjectDiffer $differ = null,
        ?DocGenPathResolver $pathResolver = null,
    ) {
        $this->repository = $repository ?? new GitRepository();
        $this->worktree = $worktree ?? new GitWorktree();
        $this->analyzer = $analyzer ?? new ProjectAnalyzer();
        $this->differ = $differ ?? new ProjectDiffer();
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
    }

    /**
     * Checks both revisions out and diffs them into one session.
     *
     * @param ?int $workers how many workers to analyze with, or null for the default
     * @param ?ParseCache $cache what earlier runs already parsed, if it is kept
     *
     * @throws DocGenException when a revision cannot be read or analyzed
     */
    public function open(DocGenConfig $config, RevisionRange $range, ?int $workers = null, ?ParseCache $cache = null): DiffSession
    {
        $repositoryRoot = $this->repository->root($config->root);
        $basePath = null;
        $headPath = null;

        try {
            $basePath = $this->checkout($repositoryRoot, $range->base);
            $baseModel = $this->analyzer->analyze($this->configFor($config, $basePath, null), $workers, $cache);
            $headPath = $range->head !== null ? $this->checkout($repositoryRoot, $range->head) : null;
            $headModel = $this->analyzer->analyze(
                $headPath === null ? $config : $this->configFor($config, $headPath, $this->coverage($config)),
                $workers,
                $cache,
            );
            $index = new DiffIndex($this->label($repositoryRoot, $range->base), $this->headLabel($repositoryRoot, $range->head), $basePath);

            return new DiffSession($this->differ->diff($baseModel, $headModel, $index), $index, $repositoryRoot, $basePath, $headPath);
        } catch (DocGenException $exception) {
            $this->removeAll($repositoryRoot, $basePath, $headPath);

            throw $exception;
        }
    }

    /**
     * Closes one session and removes its checkouts.
     */
    public function close(DiffSession $session): void
    {
        $this->removeAll($session->repositoryRoot, $session->basePath, $session->headPath);
    }

    /**
     * Checks one revision out into a linked temporary worktree.
     *
     * @throws DocGenException when the revision cannot be checked out
     */
    public function checkout(string $repositoryRoot, string $revision): string
    {
        $path = $this->worktree->create($repositoryRoot, $this->repository->commit($repositoryRoot, $revision));
        $this->worktree->linkVendor($repositoryRoot, $path);
        $canonical = realpath($path);

        return $canonical === false ? $path : $canonical;
    }

    /**
     * Removes every checkout of one session.
     */
    public function removeAll(string $repositoryRoot, ?string $basePath, ?string $headPath): void
    {
        foreach ([$basePath, $headPath] as $path) {
            if ($path !== null) {
                $this->worktree->remove($repositoryRoot, $path);
            }
        }
    }

    /**
     * Builds the configuration that documents one checkout.
     *
     * The documented scope is the configured one; only the root it is read
     * from moves, so both revisions are documented by the same rules. Where
     * the site is published and which repository it documents are properties
     * of the comparison rather than of a revision in it, so both revisions
     * carry what the run was configured with.
     *
     * @param string $root the checkout the revision was written to
     * @param ?string $coverage the coverage report of the revision, if any
     */
    public function configFor(DocGenConfig $config, string $root, ?string $coverage): DocGenConfig
    {
        return new DocGenConfig(
            $root,
            $config->packages,
            $config->vendor,
            $config->exclude,
            $config->output,
            $config->title,
            $config->deptrac,
            $coverage,
            $config->vendorDev,
            $config->cache,
            $config->baseUrl,
            $config->repository,
        );
    }

    /**
     * Resolves the coverage report against the working tree of the project.
     */
    public function coverage(DocGenConfig $config): ?string
    {
        return $config->coverage === null ? null : $this->pathResolver->resolve($config->root, $config->coverage);
    }

    /**
     * Returns the display label of one revision.
     */
    public function label(string $repositoryRoot, string $revision): string
    {
        return $this->repository->label($repositoryRoot, $revision);
    }

    /**
     * Returns the display label of the compared head revision.
     */
    public function headLabel(string $repositoryRoot, ?string $revision): string
    {
        return $revision === null ? self::WORKING_TREE : $this->label($repositoryRoot, $revision);
    }
}
