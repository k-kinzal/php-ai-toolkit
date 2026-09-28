<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Cli;

use function array_shift;

use Closure;

use function sprintf;

use Toolkit\DocGuard\Analysis\DocGuardAnalyzer;
use Toolkit\DocGuard\Config\ConfigLoader;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Generation\ConfigGenerator;
use Toolkit\DocGuard\Reporting\ReporterFactory;

/**
 * CLI entry point for DocGuard.
 */
final class Application
{
    private const VERSION = '1.0.0';

    /** @readonly */
    private DocGuardOutputWriter $writer;

    /** @readonly */
    private DocGuardCliArgumentParser $argumentParser;

    /** @readonly */
    private DocGuardHelpText $helpText;

    /** @readonly */
    private DocGuardAnalysisRunner $analysisRunner;

    /** @readonly */
    private DocGuardGenerateRunner $generateRunner;

    /**
     * Creates the DocGuard CLI application for a project working directory.
     */
    public function __construct(
        string $workingDirectory,
        ?ConfigLoader $configLoader = null,
        ?DocGuardAnalyzer $analyzer = null,
        ?ReporterFactory $reporterFactory = null,
        ?Closure $stdout = null,
        ?Closure $stderr = null,
        ?DocGuardCliArgumentParser $argumentParser = null,
        ?DocGuardHelpText $helpText = null,
        ?ConfigGenerator $generator = null,
    ) {
        $this->writer = new DocGuardOutputWriter($stdout, $stderr);
        $this->argumentParser = $argumentParser ?? new DocGuardCliArgumentParser();
        $this->helpText = $helpText ?? new DocGuardHelpText();
        $this->analysisRunner = new DocGuardAnalysisRunner(
            $workingDirectory,
            $configLoader ?? new ConfigLoader(),
            $analyzer ?? new DocGuardAnalyzer(),
            $reporterFactory ?? new ReporterFactory(),
            $this->writer,
        );
        $this->generateRunner = new DocGuardGenerateRunner($workingDirectory, $generator ?? new ConfigGenerator(), $this->writer);
    }

    /**
     * Runs the command and returns the process exit code.
     *
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        array_shift($argv);
        try {
            $arguments = $this->argumentParser->parse($argv);
        } catch (DocGuardException $exception) {
            $this->writer->writeError(sprintf("DocGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        if ($arguments['help']) {
            $this->writer->write($this->helpText->text());

            return 0;
        }

        if ($arguments['version']) {
            $this->writer->write(sprintf("doc-guard %s\n", self::VERSION));

            return 0;
        }

        if ($arguments['generate']) {
            return $this->generateRunner->run($arguments['paths']);
        }

        return $this->analysisRunner->run($arguments['config'] ?? 'doc-guard.yaml', $arguments['reporter']);
    }
}
