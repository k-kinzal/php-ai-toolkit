<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli\Command;

use Closure;

use function sprintf;

/**
 * Writes skill-installation messages to the configured output channel.
 */
final class SkillInstallationWriter
{
    /**
     * @param Closure(string): void $output writer function for CLI output
     */
    public function __construct(
        /** @readonly */
        private Closure $output,
    ) {
    }

    /**
     * Writes one output line.
     */
    public function write(string $message): void
    {
        ($this->output)($message);
    }

    /**
     * Writes the final installation summary.
     *
     * @param array{installed: int, skipped: int, errors: int} $stats
     */
    public function summary(array $stats): void
    {
        $this->write(sprintf(
            'Done. %d skill(s) installed, %d skipped, %d error(s).',
            $stats['installed'],
            $stats['skipped'],
            $stats['errors'],
        ));
    }
}
