<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Cli;

use function array_shift;

use Closure;

use function count;
use function fwrite;

use PhpAiToolkit\LocGuard\Analysis\LocGuardAnalyzer;
use PhpAiToolkit\LocGuard\Config\ConfigLoader;
use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\LocGuardException;
use PhpAiToolkit\LocGuard\Reporting\ReporterFactory;

use function sprintf;

use const STDERR;
use const STDOUT;

/**
 * CLI entry point for LocGuard.
 */
final class Application
{
    private const VERSION = '1.0.0';

    /** @var Closure(string): void */
    private Closure $stdout;

    /** @var Closure(string): void */
    private Closure $stderr;

    /**
     * Creates the LocGuard CLI application for a project working directory.
     */
    public function __construct(
        private readonly string $workingDirectory,
        private readonly ConfigLoader $configLoader = new ConfigLoader(),
        private readonly LocGuardAnalyzer $analyzer = new LocGuardAnalyzer(),
        private readonly ReporterFactory $reporterFactory = new ReporterFactory(),
        ?Closure $stdout = null,
        ?Closure $stderr = null,
    ) {
        $this->stdout = $stdout ?? static function (string $message): void {
            fwrite(STDOUT, $message);
        };
        $this->stderr = $stderr ?? static function (string $message): void {
            fwrite(STDERR, $message);
        };
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        array_shift($argv);
        try {
            $arguments = $this->parseArguments($argv);
        } catch (LocGuardException $exception) {
            $this->writeError(sprintf("LocGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        if ($arguments['help']) {
            $this->write($this->helpText());

            return 0;
        }

        if ($arguments['version']) {
            $this->write(sprintf("loc-guard %s\n", self::VERSION));

            return 0;
        }

        return $this->runAnalysis($arguments['config'], $arguments['reporter']);
    }

    private function runAnalysis(string $configPath, ?string $reporterOverride): int
    {
        try {
            $config = $this->configLoader->load($this->resolvePath($configPath));
            $config = $this->overrideReporter($config, $reporterOverride);
            $result = $this->analyzer->analyze($config);
            $reporter = $this->reporterFactory->create($config->report->reporter);
        } catch (LocGuardException $exception) {
            $this->writeError(sprintf("LocGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        $this->write($reporter->report($result, $config->report));

        return $result->hasViolations() ? 1 : 0;
    }

    /**
     * @param list<string> $argv
     * @return array{config: string, help: bool, reporter: ?string, version: bool}
     */
    private function parseArguments(array $argv): array
    {
        $arguments = ['config' => 'loc.yaml', 'help' => false, 'reporter' => null, 'version' => false];

        for ($index = 0; $index < count($argv); $index++) {
            $arg = $argv[$index];
            if ($arg === '--help' || $arg === '-h') {
                $arguments['help'] = true;
            } elseif ($arg === '--version' || $arg === '-V') {
                $arguments['version'] = true;
            } elseif ($arg === '--config') {
                $arguments['config'] = $this->readOptionValue($argv, $index, '--config');
            } elseif (str_starts_with($arg, '--config=')) {
                $arguments['config'] = substr($arg, 9);
            } elseif ($arg === '--reporter' || $arg === '--format') {
                $arguments['reporter'] = $this->readOptionValue($argv, $index, $arg);
            } elseif (str_starts_with($arg, '--reporter=')) {
                $arguments['reporter'] = substr($arg, 11);
            } elseif (str_starts_with($arg, '--format=')) {
                $arguments['reporter'] = substr($arg, 9);
            } else {
                throw new LocGuardException(sprintf('Unknown option: %s', $arg));
            }
        }

        return $arguments;
    }

    /**
     * @param list<string> $argv
     */
    private function readOptionValue(array $argv, int &$index, string $option): string
    {
        if (!isset($argv[$index + 1]) || str_starts_with($argv[$index + 1], '-')) {
            throw new LocGuardException(sprintf('Missing value for %s.', $option));
        }

        return $argv[++$index];
    }

    private function overrideReporter(LocGuardConfig $config, ?string $reporter): LocGuardConfig
    {
        if ($reporter === null) {
            return $config;
        }

        return new LocGuardConfig(
            $config->root,
            $config->paths,
            $config->exclude,
            $config->limits,
            new ReportConfig($reporter, $config->report->orderBy),
        );
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return $this->workingDirectory . '/' . $path;
    }

    private function helpText(): string
    {
        return <<<'TEXT'
loc-guard checks PHP source line-count and complexity thresholds.

Usage:
  loc-guard [--config=loc.yaml] [--reporter=ai|text|json]

Options:
  --config PATH       Path to loc.yaml (default: loc.yaml)
  --reporter NAME     Reporter: ai, text, or json
  --format NAME       Alias of --reporter
  --help, -h          Show this help message
  --version, -V       Show version

TEXT;
    }

    private function write(string $message): void
    {
        ($this->stdout)($message);
    }

    private function writeError(string $message): void
    {
        ($this->stderr)($message);
    }
}
