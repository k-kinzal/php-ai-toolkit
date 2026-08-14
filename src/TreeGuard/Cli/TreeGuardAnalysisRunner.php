<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Cli;

use PhpAiToolkit\TreeGuard\Analysis\TreeGuardAnalyzer;
use PhpAiToolkit\TreeGuard\Config\ConfigLoader;
use PhpAiToolkit\TreeGuard\Reporting\ReporterFactory;
use PhpAiToolkit\TreeGuard\TreeGuardException;

use function sprintf;

/**
 * Runs TreeGuard analysis from resolved CLI options.
 */
final class TreeGuardAnalysisRunner
{
    /** @readonly */
    private TreeGuardConfigPathResolver $pathResolver;

    /** @readonly */
    private TreeGuardReporterOverride $reporterOverride;

    /**
     * Creates an analysis runner from TreeGuard services.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        /** @readonly */
        private ConfigLoader $configLoader,
        /** @readonly */
        private TreeGuardAnalyzer $analyzer,
        /** @readonly */
        private ReporterFactory $reporterFactory,
        /** @readonly */
        private TreeGuardOutputWriter $writer,
        ?TreeGuardConfigPathResolver $pathResolver = null,
        ?TreeGuardReporterOverride $reporterOverride = null,
    ) {
        $this->pathResolver = $pathResolver ?? new TreeGuardConfigPathResolver();
        $this->reporterOverride = $reporterOverride ?? new TreeGuardReporterOverride();
    }

    /**
     * Runs analysis and writes the selected report.
     */
    public function run(string $configPath, ?string $reporterOverride): int
    {
        try {
            $config = $this->configLoader->load($this->pathResolver->resolve($this->workingDirectory, $configPath));
            $config = $this->reporterOverride->apply($config, $reporterOverride);
            $result = $this->analyzer->analyze($config);
            $reporter = $this->reporterFactory->create($config->report->reporter);
        } catch (TreeGuardException $exception) {
            $this->writer->writeError(sprintf("TreeGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        $this->writer->write($reporter->report($result, $config->report));

        return $result->hasViolations() ? 1 : 0;
    }
}
