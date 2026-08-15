<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cli;

use function count;
use function is_file;

use PhpAiToolkit\DocGen\Analysis\ProjectAnalyzer;
use PhpAiToolkit\DocGen\Config\ConfigLoader;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Render\SiteRenderer;

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
    }

    /**
     * Generates the site and optionally serves it.
     *
     * @param array{config: ?string, output: ?string, vendor: ?list<string>, vendorDev: ?list<string>, coverage: ?string, serve: ?string, memoryLimit: ?string, help: bool, version: bool} $arguments
     */
    public function run(array $arguments): int
    {
        $this->memoryLimit->apply($arguments['memoryLimit']);

        try {
            $config = $this->overrides->apply($this->loadConfig($arguments['config']), $arguments);
            $model = $this->analyzer->analyze($config);
            $outputRoot = $this->pathResolver->resolve($config->root, $config->output);
            $pages = $this->siteRenderer->render($model, $outputRoot);
            $this->writer->write(sprintf(
                "Generated %d pages for %d packages into %s\n",
                $pages,
                count($model->packages),
                $outputRoot,
            ));
            foreach ($model->warnings as $warning) {
                $this->writer->writeError(sprintf("Warning: %s\n", $warning));
            }

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
