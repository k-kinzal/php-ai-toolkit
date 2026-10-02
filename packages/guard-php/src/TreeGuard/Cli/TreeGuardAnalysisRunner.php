<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Cli;

use function sprintf;

use Toolkit\TreeGuard\Analysis\TreeGuardAnalyzer;
use Toolkit\TreeGuard\Config\ConfigLoader;
use Toolkit\TreeGuard\Reporting\ReporterFactory;
use Toolkit\TreeGuard\TreeGuardException;

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
