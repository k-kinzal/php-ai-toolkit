<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli;

use PhpAiToolkit\Installer\Cli\Command\InstallCommand;

use function sprintf;

/**
 * Runs the install command with application header output.
 */
final class ApplicationInstallRunner
{
    /**
     * Creates an install runner for a project and package root.
     */
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $packageRoot,
        private readonly CliOutputWriter $writer,
        private readonly string $version,
    ) {
    }

    /**
     * Runs skill installation and returns the install command exit code.
     */
    public function run(bool $force, bool $copy): int
    {
        $this->writer->write(sprintf('php-ai-toolkit v%s', $this->version));
        $this->writer->write('');
        $this->writer->write('Installing skills...');

        $installCommand = new InstallCommand(
            $this->projectRoot,
            $this->packageRoot,
            function (string $message): void {
                $this->writer->write($message);
            },
        );

        return $installCommand->execute($force, $copy);
    }
}
