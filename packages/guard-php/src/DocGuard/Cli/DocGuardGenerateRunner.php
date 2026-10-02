<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Cli;

use function sprintf;

use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Generation\ConfigGenerator;

/**
 * Prints a generated doc-guard.yaml for the working directory.
 */
final class DocGuardGenerateRunner
{
    /**
     * Creates a generate runner from the working directory, the generator, and output.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        /** @readonly */
        private ConfigGenerator $generator,
        /** @readonly */
        private DocGuardOutputWriter $writer,
    ) {
    }

    /**
     * Writes the generated config to standard output.
     *
     * @param list<string> $paths
     */
    public function run(array $paths): int
    {
        try {
            $yaml = $this->generator->generate($this->workingDirectory, $paths);
        } catch (DocGuardException $exception) {
            $this->writer->writeError(sprintf("DocGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        $this->writer->write($yaml);

        return 0;
    }
}
