<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli;

use function sprintf;

/**
 * Prints php-ai-toolkit CLI help text.
 */
final class ApplicationHelpPrinter
{
    /**
     * Creates a help printer for the application version.
     */
    public function __construct(
        /** @readonly */
        private CliOutputWriter $writer,
        /** @readonly */
        private string $version,
    ) {
    }

    /**
     * Prints the CLI help text.
     */
    public function print(): void
    {
        $this->writer->write(sprintf('php-ai-toolkit v%s', $this->version));
        $this->writer->write('');
        $this->writer->write('Usage: php-ai-toolkit [command] [options]');
        $this->writer->write('');
        $this->writer->write('Commands:');
        $this->writer->write('  install          Install skills into the project (default)');
        $this->writer->write('');
        $this->writer->write('Options:');
        $this->writer->write('  --force, -f      Overwrite existing skills');
        $this->writer->write('  --copy           Copy files instead of creating symlinks');
        $this->writer->write('  --help, -h       Show this help message');
        $this->writer->write('  --version, -V    Show version');
    }
}
