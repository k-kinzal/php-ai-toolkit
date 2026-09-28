<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Cli;

use function count;
use function explode;
use function sprintf;

use Toolkit\DocGuard\DocGuardException;

/**
 * Parses DocGuard command-line arguments.
 */
final class DocGuardCliArgumentParser
{
    /** @var array<string, 'generate'|'help'|'version'> */
    private const FLAGS = [
        '--help' => 'help',
        '-h' => 'help',
        '--version' => 'version',
        '-V' => 'version',
        '--generate' => 'generate',
    ];

    /** @var array<string, 'config'|'reporter'> */
    private const VALUE_OPTIONS = [
        '--config' => 'config',
        '--reporter' => 'reporter',
        '--format' => 'reporter',
    ];

    /**
     * Parses DocGuard flags, options, and the paths given to --generate.
     *
     * The config is null when --config is not given.
     *
     * @param list<string> $argv
     * @return array{config: ?string, generate: bool, help: bool, paths: list<string>, reporter: ?string, version: bool}
     *
     * @throws DocGuardException when an option is unknown, is missing its value, or cannot be combined
     */
    public function parse(array $argv): array
    {
        $flags = ['generate' => false, 'help' => false, 'version' => false];
        $values = ['config' => null, 'reporter' => null];
        $paths = [];
        $count = count($argv);

        for ($index = 0; $index < $count; $index++) {
            $arg = $argv[$index];
            if (isset(self::FLAGS[$arg])) {
                $flags[self::FLAGS[$arg]] = true;
                continue;
            }

            $parts = explode('=', $arg, 2);
            $name = $parts[0];
            $value = $parts[1] ?? null;
            if (isset(self::VALUE_OPTIONS[$name])) {
                if ($value === null) {
                    $value = $argv[$index + 1] ?? null;
                    if ($value === null || str_starts_with($value, '-')) {
                        throw new DocGuardException(sprintf('Missing value for %s.', $name));
                    }
                    $index++;
                }
                $values[self::VALUE_OPTIONS[$name]] = $value;
                continue;
            }

            if (str_starts_with($arg, '-')) {
                throw new DocGuardException(sprintf('Unknown option: %s', $arg));
            }
            $paths[] = $arg;
        }

        $arguments = [
            'config' => $values['config'],
            'generate' => $flags['generate'],
            'help' => $flags['help'],
            'paths' => $paths,
            'reporter' => $values['reporter'],
            'version' => $flags['version'],
        ];
        $this->validate($arguments);

        return $arguments;
    }

    /**
     * Rejects paths outside --generate and options that --generate does not read.
     *
     * @param array{config: ?string, generate: bool, paths: list<string>, reporter: ?string} $arguments
     *
     * @throws DocGuardException when the arguments cannot be combined
     */
    public function validate(array $arguments): void
    {
        if ($arguments['paths'] !== [] && !$arguments['generate']) {
            throw new DocGuardException(sprintf('Unexpected argument: %s. Paths are accepted only with --generate.', $arguments['paths'][0]));
        }

        if ($arguments['generate'] && ($arguments['config'] !== null || $arguments['reporter'] !== null)) {
            throw new DocGuardException('--generate prints a new config to standard output and cannot be combined with --config, --reporter, or --format.');
        }
    }
}
