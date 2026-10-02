<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Cli;

use function sprintf;

use Toolkit\DocGuard\Analysis\DocGuardAnalyzer;
use Toolkit\DocGuard\Config\ConfigLoader;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Reporting\ReporterFactory;

/**
 * Runs DocGuard analysis from resolved CLI options.
 */
final class DocGuardAnalysisRunner
{
    /** @readonly */
    private DocGuardConfigPathResolver $pathResolver;

    /** @readonly */
    private DocGuardReporterOverride $reporterOverride;

    /**
     * Creates an analysis runner from DocGuard services.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        /** @readonly */
        private ConfigLoader $configLoader,
        /** @readonly */
        private DocGuardAnalyzer $analyzer,
        /** @readonly */
        private ReporterFactory $reporterFactory,
        /** @readonly */
        private DocGuardOutputWriter $writer,
        ?DocGuardConfigPathResolver $pathResolver = null,
        ?DocGuardReporterOverride $reporterOverride = null,
    ) {
        $this->pathResolver = $pathResolver ?? new DocGuardConfigPathResolver();
        $this->reporterOverride = $reporterOverride ?? new DocGuardReporterOverride();
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
        } catch (DocGuardException $exception) {
            $this->writer->writeError(sprintf("DocGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        $this->writer->write($reporter->report($result, $config->report));

        return $result->hasViolations() ? 1 : 0;
    }
}
