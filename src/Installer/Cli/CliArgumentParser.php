<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli;

/**
 * Parses php-ai-toolkit CLI arguments into command flags.
 */
final class CliArgumentParser
{
    /**
     * Parses command-line arguments after the binary name has been removed.
     *
     * @param list<string> $argv
     * @return array{command: string|null, force: bool, copy: bool, help: bool, version: bool}
     */
    public function parse(array $argv): array
    {
        $arguments = [
            'command' => null,
            'force' => false,
            'copy' => false,
            'help' => false,
            'version' => false,
        ];

        foreach ($argv as $arg) {
            if ($arg === '--help' || $arg === '-h') {
                $arguments['help'] = true;
            } elseif ($arg === '--version' || $arg === '-V') {
                $arguments['version'] = true;
            } elseif ($arg === '--force' || $arg === '-f') {
                $arguments['force'] = true;
            } elseif ($arg === '--copy') {
                $arguments['copy'] = true;
            } elseif (!str_starts_with($arg, '-') && $arguments['command'] === null) {
                $arguments['command'] = $arg;
            }
        }

        return $arguments;
    }
}
