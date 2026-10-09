<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Action;

use function count;
use function sprintf;

use Toolkit\DocGen\Action\Config\DocGenConfig;
use Toolkit\DocGen\Action\Revision\DiffWorkspace;
use Toolkit\DocGen\Action\Revision\Git\RevisionRange;
use Toolkit\DocGen\Cache\CacheStore;
use Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Parse\Cache\ParseCache;
use Toolkit\DocGen\Report\Cache\RenderCache;
use Toolkit\DocGen\Report\SiteRenderer;

/**
 * Executes a documentation request and returns the completed run summary.
 */
final class GenerateDocumentation
{
    /** @readonly */
    private ProjectAnalysis $analyzer;
    /** @readonly */
    private SiteRenderer $siteRenderer;
    /** @readonly */
    private DiffWorkspace $workspace;
    /** @readonly */
    private CacheStore $store;
    /** @readonly */
    private DocGenPathResolver $pathResolver;

    /**
     * Creates the action that connects analysis, comparison and reporting.
     */
    public function __construct(
        ?ProjectAnalysis $analyzer = null,
        ?SiteRenderer $siteRenderer = null,
        ?DiffWorkspace $workspace = null,
        ?CacheStore $store = null,
        ?DocGenPathResolver $pathResolver = null,
    ) {
        $this->analyzer = $analyzer ?? new ProjectAnalysis();
        $this->siteRenderer = $siteRenderer ?? new SiteRenderer();
        $this->workspace = $workspace ?? new DiffWorkspace(null, null, $this->analyzer);
        $this->store = $store ?? new CacheStore();
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
    }

    /**
     * Generates the requested site, keeping CLI concerns outside the action.
     *
     * @throws DocGenException when a project, revision or output cannot be read or written
     */
    public function run(GenerationRequest $request): GenerationResult
    {
        $config = $request->config;
        $outputRoot = $this->pathResolver->resolve($config->root, $config->output);
        if ($request->clearCache) {
            $this->clear($config->root, $request->cacheDirectory);
        }

        $cache = $this->caches($config, $outputRoot);
        $result = $request->base === null
            ? $this->generate($config, $outputRoot, $request->workers, $cache)
            : $this->generateDiff($config, $outputRoot, new RevisionRange($request->base, $request->head), $request->workers, $cache);
        $cache->save();

        return $result;
    }

    /**
     * Analyzes and renders the selected working tree.
     *
     * @throws DocGenException when the project cannot be analyzed or written
     */
    public function generate(DocGenConfig $config, string $outputRoot, ?int $workers = null, ?GenerationCache $cache = null): GenerationResult
    {
        $cache = $cache ?? new GenerationCache();
        $model = $this->analyzer->analyze($config, $workers, $cache->sources);
        $site = $this->siteRenderer->render($model, $outputRoot, null, $workers, $cache->pages);

        return new GenerationResult(
            $outputRoot,
            $site->pages,
            count($model->packages),
            array_merge($cache->warnings, $model->warnings, $site->warnings),
            $cache->summary(),
        );
    }

    /**
     * Compares completed project analyses and renders while both checkouts exist.
     *
     * @throws DocGenException when a revision cannot be analyzed or written
     */
    public function generateDiff(DocGenConfig $config, string $outputRoot, RevisionRange $range, ?int $workers = null, ?GenerationCache $cache = null): GenerationResult
    {
        $cache = $cache ?? new GenerationCache();
        $session = $this->workspace->open($config, $range, $workers, $cache->sources);
        try {
            $site = $this->siteRenderer->render($session->model, $outputRoot, $session->diff, $workers, $cache->pages);

            return new GenerationResult(
                $outputRoot,
                $site->pages,
                count($session->model->packages),
                array_merge($cache->warnings, $session->model->warnings, $site->warnings),
                $cache->summary(),
                $session->diff->baseLabel(),
                $session->diff->headLabel(),
            );
        } finally {
            $this->workspace->close($session);
        }
    }

    /**
     * Builds the caches of one run, or the absence of them.
     *
     * Both caches are read before anything is analyzed or rendered, so the
     * worker processes of the run inherit them instead of reading the same
     * files once per worker.
     */
    public function caches(DocGenConfig $config, string $outputRoot): GenerationCache
    {
        if ($config->cache === null) {
            return new GenerationCache();
        }

        $directory = $this->pathResolver->resolve($config->root, $config->cache);
        if (!$this->store->prepare($directory)) {
            return new GenerationCache(null, null, [sprintf('nothing is cached, because the cache directory cannot be written: %s', $directory)]);
        }

        $cache = new GenerationCache(new ParseCache($directory), new RenderCache($directory, $outputRoot));
        $cache->load();

        return $cache;
    }

    /**
     * Removes one cache directory before the run that was told to.
     */
    public function clear(string $root, string $directory): void
    {
        $this->store->clear($this->pathResolver->resolve($root, $directory));
    }
}
