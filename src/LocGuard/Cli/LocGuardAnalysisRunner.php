<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Cli;

use PhpAiToolkit\LocGuard\Analysis\LocGuardAnalyzer;
use PhpAiToolkit\LocGuard\Config\ConfigLoader;
use PhpAiToolkit\LocGuard\LocGuardException;
use PhpAiToolkit\LocGuard\Reporting\ReporterFactory;

use function sprintf;

/**
 * Runs LocGuard analysis from resolved CLI options.
 */
final class LocGuardAnalysisRunner
{
    /**
     * Creates an analysis runner from LocGuard services.
     */
    public function __construct(
        private readonly string $workingDirectory,
        private readonly ConfigLoader $configLoader,
        private readonly LocGuardAnalyzer $analyzer,
        private readonly ReporterFactory $reporterFactory,
        private readonly LocGuardOutputWriter $writer,
        private readonly LocGuardConfigPathResolver $pathResolver = new LocGuardConfigPathResolver(),
        private readonly LocGuardReporterOverride $reporterOverride = new LocGuardReporterOverride(),
    ) {
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
        } catch (LocGuardException $exception) {
            $this->writer->writeError(sprintf("LocGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        $this->writer->write($reporter->report($result, $config->report));

        return $result->hasViolations() ? 1 : 0;
    }
}
