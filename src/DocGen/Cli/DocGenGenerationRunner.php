<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cli;

use function count;
use function sprintf;

use Toolkit\DocGen\Analysis\Diff\DiffWorkspace;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Cache\CacheStore;
use Toolkit\DocGen\Cache\GenerationCache;
use Toolkit\DocGen\Cache\ParseCache;
use Toolkit\DocGen\Cache\RenderCache;
use Toolkit\DocGen\Config\DocGenConfig;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Git\RevisionRange;
use Toolkit\DocGen\Render\SiteRenderer;
use Toolkit\DocGen\Render\Social\SocialCard;

/**
 * Runs the documentation generation for parsed CLI arguments.
 */
final class DocGenGenerationRunner
{
    /** @readonly */
    private string $workingDirectory;

    /** @readonly */
    private ProjectAnalyzer $analyzer;

    /** @readonly */
    private SiteRenderer $siteRenderer;

    /** @readonly */
    private DocGenOutputWriter $writer;

    /** @readonly */
    private DocGenConfigFactory $configFactory;

    /** @readonly */
    private DocGenPathResolver $pathResolver;

    /** @readonly */
    private DocGenPreviewServer $previewServer;

    /** @readonly */
    private DocGenMemoryLimit $memoryLimit;

    /** @readonly */
    private DiffWorkspace $workspace;

    /** @readonly */
    private CacheStore $store;

    /** @readonly */
    private SocialCard $card;

    /**
     * Creates a generation runner from pipeline collaborators.
     */
    public function __construct(
        string $workingDirectory,
        ?ProjectAnalyzer $analyzer = null,
        ?SiteRenderer $siteRenderer = null,
        ?DocGenOutputWriter $writer = null,
        ?DocGenConfigFactory $configFactory = null,
        ?DocGenPathResolver $pathResolver = null,
        ?DocGenPreviewServer $previewServer = null,
        ?DocGenMemoryLimit $memoryLimit = null,
        ?DiffWorkspace $workspace = null,
        ?CacheStore $store = null,
        ?SocialCard $card = null,
    ) {
        $this->workingDirectory = $workingDirectory;
        $this->analyzer = $analyzer ?? new ProjectAnalyzer();
        $this->siteRenderer = $siteRenderer ?? new SiteRenderer();
        $this->writer = $writer ?? new DocGenOutputWriter();
        $this->configFactory = $configFactory ?? new DocGenConfigFactory();
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
        $this->previewServer = $previewServer ?? new DocGenPreviewServer();
        $this->memoryLimit = $memoryLimit ?? new DocGenMemoryLimit();
        $this->workspace = $workspace ?? new DiffWorkspace(null, null, $this->analyzer);
        $this->store = $store ?? new CacheStore();
        $this->card = $card ?? new SocialCard();
    }

    /**
     * Generates the site and optionally serves it.
     *
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, publicApi?: bool, noCache: bool, clearCache: bool, help: bool, version: bool} $arguments
     */
    public function run(array $arguments): int
    {
        $this->memoryLimit->apply($arguments['memoryLimit']);

        try {
            $config = $this->configFactory->create($this->workingDirectory, $arguments);
            $outputRoot = $this->pathResolver->resolve($config->root, $config->output);
            if ($arguments['clearCache']) {
                $this->clear($config->root, $this->configFactory->cacheDirectory($arguments));
            }

            $cache = $this->caches($config, $outputRoot);
            $result = $arguments['base'] === null
                ? $this->generate($config, $outputRoot, $arguments['jobs'], $cache)
                : $this->generateDiff($config, $outputRoot, new RevisionRange($arguments['base'], $arguments['head']), $arguments['jobs'], $cache);
            $this->report($result['model'], $result['pages'], $outputRoot, $cache);

            if ($arguments['serve'] !== null) {
                $this->writer->write(sprintf("Serving documentation at http://%s (Ctrl-C to stop)\n", $arguments['serve']));

                return $this->previewServer->serve($outputRoot, $arguments['serve']);
            }
        } catch (DocGenException $exception) {
            $this->writer->writeError(sprintf("DocGen error: %s\n", $exception->getMessage()));

            return 2;
        }

        return 0;
    }

    /**
     * Analyzes and renders the documented project as it is now.
     *
     * @return array{model: ProjectModel, pages: int}
     *
     * @throws DocGenException when the project cannot be analyzed or written
     */
    public function generate(DocGenConfig $config, string $outputRoot, ?int $workers = null, ?GenerationCache $cache = null): array
    {
        $cache = $cache ?? new GenerationCache();
        $model = $this->analyzer->analyze($config, $workers, $cache->sources);

        return ['model' => $model, 'pages' => $this->siteRenderer->render($model, $outputRoot, null, $workers, $cache->pages)];
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
            $this->writer->writeError(sprintf("Warning: nothing is cached, because the cache directory cannot be written: %s\n", $directory));

            return new GenerationCache();
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

    /**
     * Renders the documented project as the comparison of two revisions.
     *
     * The checkouts of the compared revisions are removed again whatever
     * happens while the site is written, because they are scratch copies
     * of the repository and nothing may outlive the run.
     *
     * @return array{model: ProjectModel, pages: int}
     *
     * @throws DocGenException when a revision cannot be read or analyzed
     */
    public function generateDiff(DocGenConfig $config, string $outputRoot, RevisionRange $range, ?int $workers = null, ?GenerationCache $cache = null): array
    {
        $cache = $cache ?? new GenerationCache();
        $session = $this->workspace->open($config, $range, $workers, $cache->sources);

        try {
            $pages = $this->siteRenderer->render($session->model, $outputRoot, $session->diff, $workers, $cache->pages);
            $this->writer->write(sprintf(
                "Compared %s to %s\n",
                $session->diff->baseLabel(),
                $session->diff->headLabel(),
            ));

            return ['model' => $session->model, 'pages' => $pages];
        } finally {
            $this->workspace->close($session);
        }
    }

    /**
     * Reports what was written and what the analysis could not do.
     */
    public function report(ProjectModel $model, int $pages, string $outputRoot, ?GenerationCache $cache = null): void
    {
        $this->writer->write(sprintf(
            "Generated %d pages for %d packages into %s\n",
            $pages,
            count($model->packages),
            $outputRoot,
        ));
        if ($cache !== null) {
            $this->reportCache($cache);
        }

        foreach ($model->warnings as $warning) {
            $this->writer->writeError(sprintf("Warning: %s\n", $warning));
        }

        if ($model->baseUrl !== null && !$this->card->supported()) {
            $this->writer->writeError(
                'Warning: no social preview image was drawn, because the gd extension with FreeType support is missing.'
                . " The pages carry their preview tags without an image.\n",
            );
        }
    }

    /**
     * Writes what the caches held back and keeps what this run learned.
     */
    public function reportCache(GenerationCache $cache): void
    {
        $summary = $cache->summary();
        if ($summary !== null) {
            $this->writer->write($summary . "\n");
        }

        $cache->save();
    }
}
