<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Cli;

use PhpAiToolkit\ScopeGuard\Analysis\ScopeGuardAnalyzer;
use PhpAiToolkit\ScopeGuard\Config\ConfigLoader;
use PhpAiToolkit\ScopeGuard\Reporting\ReporterFactory;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;

use function sprintf;

/**
 * Runs ScopeGuard analysis from resolved CLI options.
 *
 * @visibility namespace
 */
final class ScopeGuardAnalysisRunner
{
    /** @readonly */
    private ScopeGuardConfigPathResolver $pathResolver;

    /** @readonly */
    private ScopeGuardReporterOverride $reporterOverride;

    /**
     * Creates an analysis runner from ScopeGuard services.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        /** @readonly */
        private ConfigLoader $configLoader,
        /** @readonly */
        private ScopeGuardAnalyzer $analyzer,
        /** @readonly */
        private ReporterFactory $reporterFactory,
        /** @readonly */
        private ScopeGuardOutputWriter $writer,
        ?ScopeGuardConfigPathResolver $pathResolver = null,
        ?ScopeGuardReporterOverride $reporterOverride = null,
    ) {
        $this->pathResolver = $pathResolver ?? new ScopeGuardConfigPathResolver();
        $this->reporterOverride = $reporterOverride ?? new ScopeGuardReporterOverride();
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
        } catch (ScopeGuardException $exception) {
            $this->writer->writeError(sprintf("ScopeGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        $this->writer->write($reporter->report($result, $config->report));

        return $result->hasViolations() ? 1 : 0;
    }
}
