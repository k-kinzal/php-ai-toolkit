<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cli;

use function count;
use function is_file;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffWorkspace;
use PhpAiToolkit\DocGen\Analysis\ProjectAnalyzer;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Cache\CacheStore;
use PhpAiToolkit\DocGen\Cache\GenerationCache;
use PhpAiToolkit\DocGen\Cache\ParseCache;
use PhpAiToolkit\DocGen\Cache\RenderCache;
use PhpAiToolkit\DocGen\Config\ConfigLoader;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Git\RevisionRange;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SocialCard;

use function realpath;
use function sprintf;

/**
 * Runs the documentation generation for parsed CLI arguments.
 */
final class DocGenGenerationRunner
{
    /** @readonly */
    private string $workingDirectory;

    /** @readonly */
    private ConfigLoader $configLoader;

    /** @readonly */
    private ProjectAnalyzer $analyzer;

    /** @readonly */
    private SiteRenderer $siteRenderer;

    /** @readonly */
    private DocGenOutputWriter $writer;

    /** @readonly */
    private DocGenConfigPathResolver $configPathResolver;

    /** @readonly */
    private DocGenConfigOverrides $overrides;

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
        ?ConfigLoader $configLoader = null,
        ?ProjectAnalyzer $analyzer = null,
        ?SiteRenderer $siteRenderer = null,
        ?DocGenOutputWriter $writer = null,
        ?DocGenConfigPathResolver $configPathResolver = null,
        ?DocGenConfigOverrides $overrides = null,
        ?DocGenPathResolver $pathResolver = null,
        ?DocGenPreviewServer $previewServer = null,
        ?DocGenMemoryLimit $memoryLimit = null,
        ?DiffWorkspace $workspace = null,
        ?CacheStore $store = null,
        ?SocialCard $card = null,
    ) {
        $this->workingDirectory = $workingDirectory;
        $this->configLoader = $configLoader ?? new ConfigLoader();
        $this->analyzer = $analyzer ?? new ProjectAnalyzer();
        $this->siteRenderer = $siteRenderer ?? new SiteRenderer();
        $this->writer = $writer ?? new DocGenOutputWriter();
        $this->configPathResolver = $configPathResolver ?? new DocGenConfigPathResolver();
        $this->overrides = $overrides ?? new DocGenConfigOverrides();
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
     * @param array{config: ?string, output: ?string, vendor: ?list<string>, vendorDev: ?list<string>, coverage: ?string, baseUrl: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, cacheDir: ?string, noCache: bool, clearCache: bool, help: bool, version: bool} $arguments
     */
    public function run(array $arguments): int
    {
        $this->memoryLimit->apply($arguments['memoryLimit']);

        try {
            $loaded = $this->loadConfig($arguments['config']);
            $config = $this->overrides->apply($loaded, $arguments);
            $outputRoot = $this->pathResolver->resolve($config->root, $config->output);
            if ($arguments['clearCache']) {
                $this->clear($config->root, $arguments['cacheDir'] ?? $loaded->cache);
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
    public function clear(string $root, ?string $directory): void
    {
        if ($directory !== null) {
            $this->store->clear($this->pathResolver->resolve($root, $directory));
        }
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

    /**
     * Loads the configuration file, or builds the zero-config defaults.
     *
     * @throws DocGenException when an explicitly given config file is invalid
     */
    public function loadConfig(?string $configOption): DocGenConfig
    {
        if ($configOption !== null) {
            return $this->configLoader->load($this->configPathResolver->resolve($this->workingDirectory, $configOption));
        }

        $default = $this->configPathResolver->resolve($this->workingDirectory, 'doc.yaml');
        if (is_file($default)) {
            return $this->configLoader->load($default);
        }

        $root = realpath($this->workingDirectory);

        return new DocGenConfig($root === false ? $this->workingDirectory : $root, ['.', 'packages/*'], [], [], 'build/docs', null, null, null);
    }
}
