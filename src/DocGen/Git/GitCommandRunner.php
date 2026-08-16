<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Git;

use Closure;

use function escapeshellarg;
use function exec;
use function implode;

use PhpAiToolkit\DocGen\DocGenException;

use function sprintf;
use function trim;

/**
 * Runs the git commands a diff site is generated from.
 *
 * The process launcher is injectable so the command line is verifiable
 * without a repository, and so a failing git install produces one
 * actionable message instead of a stack of warnings.
 */
final class GitCommandRunner
{
    /**
     * @var Closure(string): array{status: int, output: string}
     *
     * @readonly
     */
    private Closure $launcher;

    /**
     * Creates a git runner with an injectable process launcher.
     *
     * @param ?Closure(string): array{status: int, output: string} $launcher
     */
    public function __construct(?Closure $launcher = null)
    {
        $this->launcher = $launcher ?? static function (string $command): array {
            $lines = [];
            $status = 0;
            exec($command . ' 2>&1', $lines, $status);

            return ['status' => $status, 'output' => trim(implode("\n", $lines))];
        };
    }

    /**
     * Runs one git command and returns its trimmed output.
     *
     * @param list<string> $arguments
     *
     * @throws DocGenException when the command fails
     */
    public function run(array $arguments, string $workingDirectory): string
    {
        $result = $this->execute($arguments, $workingDirectory);
        if ($result['status'] !== 0) {
            throw new DocGenException(sprintf(
                'git %s failed in %s: %s',
                implode(' ', $arguments),
                $workingDirectory,
                $result['output'] === '' ? 'no output' : $result['output'],
            ));
        }

        return $result['output'];
    }

    /**
     * Runs one git command and reports its status with its output.
     *
     * @param list<string> $arguments
     *
     * @return array{status: int, output: string}
     */
    public function execute(array $arguments, string $workingDirectory): array
    {
        return ($this->launcher)($this->command($arguments, $workingDirectory));
    }

    /**
     * Builds the escaped command line of one git invocation.
     *
     * @param list<string> $arguments
     */
    public function command(array $arguments, string $workingDirectory): string
    {
        $parts = ['git', '-C', escapeshellarg($workingDirectory)];
        foreach ($arguments as $argument) {
            $parts[] = escapeshellarg($argument);
        }

        return implode(' ', $parts);
    }
}
