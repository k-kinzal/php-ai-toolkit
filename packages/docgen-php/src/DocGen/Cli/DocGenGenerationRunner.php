<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cli;

use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\DocGenException;

/**
 * Adapts command-line arguments to a generation request and prints its result.
 */
final class DocGenGenerationRunner
{
    /** @readonly */
    private string $workingDirectory;
    /** @readonly */
    private GenerateDocumentation $action;
    /** @readonly */
    private DocGenOutputWriter $writer;
    /** @readonly */
    private DocGenConfigFactory $configFactory;
    /** @readonly */
    private DocGenPreviewServer $previewServer;
    /** @readonly */
    private DocGenMemoryLimit $memoryLimit;

    /**
     * Creates the CLI adapter around a generation action.
     */
    public function __construct(
        string $workingDirectory,
        ?GenerateDocumentation $action = null,
        ?DocGenOutputWriter $writer = null,
        ?DocGenConfigFactory $configFactory = null,
        ?DocGenPreviewServer $previewServer = null,
        ?DocGenMemoryLimit $memoryLimit = null,
    ) {
        $this->workingDirectory = $workingDirectory;
        $this->action = $action ?? new GenerateDocumentation();
        $this->writer = $writer ?? new DocGenOutputWriter();
        $this->configFactory = $configFactory ?? new DocGenConfigFactory();
        $this->previewServer = $previewServer ?? new DocGenPreviewServer();
        $this->memoryLimit = $memoryLimit ?? new DocGenMemoryLimit();
    }

    /**
     * Generates the site and optionally serves it.
     *
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, publicApi?: bool, noCache: bool, clearCache: bool} $arguments
     */
    public function run(array $arguments): int
    {
        $this->memoryLimit->apply($arguments['memoryLimit']);

        try {
            $result = $this->action->run(new GenerationRequest(
                $this->configFactory->create($this->workingDirectory, $arguments),
                $arguments['jobs'],
                $arguments['base'],
                $arguments['head'],
                $arguments['clearCache'],
                $this->configFactory->cacheDirectory($arguments),
            ));
            $this->report($result);

            if ($arguments['serve'] !== null) {
                $this->writer->write(sprintf("Serving documentation at http://%s (Ctrl-C to stop)\n", $arguments['serve']));

                return $this->previewServer->serve($result->outputRoot, $arguments['serve']);
            }
        } catch (DocGenException $exception) {
            $this->writer->writeError(sprintf("DocGen error: %s\n", $exception->getMessage()));

            return 2;
        }

        return 0;
    }

    /**
     * Prints only the completed run summary returned by the action.
     */
    public function report(GenerationResult $result): void
    {
        if ($result->baseLabel !== null) {
            $this->writer->write(sprintf("Compared %s to %s\n", $result->baseLabel, $result->headLabel));
        }

        $this->writer->write(sprintf(
            "Generated %d pages for %d packages into %s\n",
            $result->pages,
            $result->packages,
            $result->outputRoot,
        ));
        if ($result->cacheSummary !== null) {
            $this->writer->write($result->cacheSummary . "\n");
        }

        foreach ($result->warnings as $warning) {
            $this->writer->writeError(sprintf("Warning: %s\n", $warning));
        }
    }
}
