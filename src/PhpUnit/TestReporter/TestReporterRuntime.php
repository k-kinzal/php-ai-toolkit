<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use Closure;
use function fwrite;
use function getcwd;

use PhpAiToolkit\Shared\AgentDetector;
use PhpAiToolkit\Shared\FormatMode;

use const STDERR;

/**
 * Shared TestReporter runtime used by PHPUnit-version-specific adapters.
 */
final class TestReporterRuntime
{
    /**
     * @param Closure(string): void $writer output writer
     */
    public function __construct(
        /** @readonly */
        private TestIssueCollector $collector,
        /** @readonly */
        private TestIssueFormatter $formatter,
        /** @readonly */
        private Closure $writer,
        /** @readonly */
        private bool $replacedOutput,
    ) {
    }

    /**
     * Creates a runtime configured from the current process environment.
     *
     * @param Closure(string): void|null $writer output writer
     */
    public static function fromCurrentProcess(?Closure $writer = null, bool $replacedOutput = false): self
    {
        $writer ??= static function (string $output): void {
            fwrite(STDERR, $output);
        };

        $agentDetector = new AgentDetector();

        return new self(
            new TestIssueCollector(),
            new TestIssueFormatter($agentDetector, (string) getcwd()),
            $writer,
            $replacedOutput,
        );
    }

    /**
     * Checks whether the current process should use AI-mode output behavior.
     */
    public static function isAiMode(): bool
    {
        return (new AgentDetector())->resolveMode() === FormatMode::AI;
    }

    /**
     * Returns the shared issue collector.
     */
    public function collector(): TestIssueCollector
    {
        return $this->collector;
    }

    /**
     * Formats and writes the final report.
     */
    public function writeReport(): void
    {
        if (!$this->collector->hasIssues()) {
            if ($this->replacedOutput) {
                ($this->writer)("No test failures\n");
            }

            return;
        }

        ($this->writer)($this->formatter->format($this->collector->getIssues()));
    }
}
