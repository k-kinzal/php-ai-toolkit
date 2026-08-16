<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Parallel;

use function count;
use function file_get_contents;
use function function_exists;
use function getenv;
use function is_readable;
use function is_string;

use const PHP_OS_FAMILY;

use function preg_match;
use function preg_match_all;
use function shell_exec;
use function trim;

/**
 * Counts the logical CPU cores available to the current machine.
 *
 * The sources are asked in the order of how much they can be trusted, and
 * the first one that answers wins. Every source may be missing on a given
 * machine — a container without procfs, a host with shell execution
 * disabled, Windows — so a machine that answers nothing counts as one core
 * and the generation simply stays sequential.
 */
final class CpuCoreCounter
{
    /**
     * The environment variables that state a core count directly.
     *
     * Windows sets NUMBER_OF_PROCESSORS itself, and container runtimes are
     * commonly configured to export it so a process sees the cores of its
     * cgroup rather than the cores of the host.
     *
     * @var list<string>
     */
    public const ENVIRONMENT_VARIABLES = ['DOCGEN_CPU_CORES', 'NUMBER_OF_PROCESSORS'];

    private ?int $cores = null;

    /**
     * Returns the number of logical cores, at least one.
     */
    public function count(): int
    {
        if ($this->cores !== null) {
            return $this->cores;
        }

        return $this->cores = $this->detect() ?? 1;
    }

    /**
     * Asks every source in turn for the core count of this machine.
     */
    public function detect(): ?int
    {
        foreach (self::ENVIRONMENT_VARIABLES as $variable) {
            $fromEnvironment = $this->fromValue(getenv($variable));
            if ($fromEnvironment !== null) {
                return $fromEnvironment;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return null;
        }

        foreach (['nproc 2>/dev/null', 'sysctl -n hw.logicalcpu 2>/dev/null', 'getconf _NPROCESSORS_ONLN 2>/dev/null'] as $command) {
            $fromCommand = $this->fromCommand($command);
            if ($fromCommand !== null) {
                return $fromCommand;
            }
        }

        return $this->fromProcCpuInfo('/proc/cpuinfo');
    }

    /**
     * Reads a core count from a raw value, or null when it states none.
     *
     * @param mixed $value the raw value, as an environment or command gives it
     */
    public function fromValue($value): ?int
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return preg_match('/^[1-9]\d*$/', $trimmed) === 1 ? (int) $trimmed : null;
    }

    /**
     * Runs a command that prints a core count, or returns null.
     *
     * Shell execution is unavailable on hosts that disable it, and the
     * commands themselves exist only on some systems, so anything other
     * than a plain positive number is treated as no answer at all.
     */
    public function fromCommand(string $command): ?int
    {
        if (!function_exists('shell_exec')) {
            return null;
        }

        return $this->fromValue(@shell_exec($command));
    }

    /**
     * Counts the processor entries of a Linux cpuinfo file.
     */
    public function fromProcCpuInfo(string $path): ?int
    {
        if (!@is_readable($path)) {
            return null;
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $matches = [];
        $found = preg_match_all('/^processor\s*:/mi', $contents, $matches);

        return $found !== false && $found > 0 ? count($matches[0]) : null;
    }
}
