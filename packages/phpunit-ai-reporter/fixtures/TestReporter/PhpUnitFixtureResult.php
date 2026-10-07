<?php

declare(strict_types=1);

namespace Tests\Fixture\TestReporter;

/**
 * The exit status and combined output of one PHPUnit fixture run.
 */
final class PhpUnitFixtureResult
{
    /**
     * @param int $exitCode process status returned by PHPUnit
     * @param string $output stdout and stderr joined in that order
     */
    public function __construct(
        private int $exitCode,
        private string $output,
    ) {
    }

    /**
     * Returns the process status PHPUnit exited with.
     */
    public function exitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * Returns stdout followed by stderr.
     */
    public function output(): string
    {
        return $this->output;
    }
}
