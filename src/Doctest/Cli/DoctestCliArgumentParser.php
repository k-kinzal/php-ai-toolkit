<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Cli;

use function count;

use PhpAiToolkit\Doctest\DoctestException;

use function sprintf;
use function str_starts_with;
use function substr;

/**
 * Parses doctest command-line arguments.
 *
 * @visibility namespace
 */
final class DoctestCliArgumentParser
{
    /**
     * Parses doctest flags and options.
     *
     * @param list<string> $argv
     * @return array{config: string, filter: ?string, help: bool, list: bool, reporter: ?string, version: bool}
     *
     * @throws DoctestException when an option is unknown or missing its value
     */
    public function parse(array $argv): array
    {
        $arguments = ['config' => 'doctest.yaml', 'filter' => null, 'help' => false, 'list' => false, 'reporter' => null, 'version' => false];

        for ($index = 0; $index < count($argv); $index++) {
            $arg = $argv[$index];
            if ($arg === '--help' || $arg === '-h') {
                $arguments['help'] = true;
            } elseif ($arg === '--version' || $arg === '-V') {
                $arguments['version'] = true;
            } elseif ($arg === '--list') {
                $arguments['list'] = true;
            } elseif ($arg === '--config') {
                $arguments['config'] = $this->value($argv, $index, $arg);
                $index++;
            } elseif (str_starts_with($arg, '--config=')) {
                $arguments['config'] = substr($arg, 9);
            } elseif ($arg === '--filter') {
                $arguments['filter'] = $this->value($argv, $index, $arg);
                $index++;
            } elseif (str_starts_with($arg, '--filter=')) {
                $arguments['filter'] = substr($arg, 9);
            } elseif ($arg === '--reporter' || $arg === '--format') {
                $arguments['reporter'] = $this->value($argv, $index, $arg);
                $index++;
            } elseif (str_starts_with($arg, '--reporter=')) {
                $arguments['reporter'] = substr($arg, 11);
            } elseif (str_starts_with($arg, '--format=')) {
                $arguments['reporter'] = substr($arg, 9);
            } else {
                throw new DoctestException(sprintf('Unknown option: %s', $arg));
            }
        }

        return $arguments;
    }

    /**
     * Returns the value that follows an option.
     *
     * @param list<string> $argv
     *
     * @throws DoctestException when the option has no value
     */
    public function value(array $argv, int $index, string $option): string
    {
        if (!isset($argv[$index + 1]) || str_starts_with($argv[$index + 1], '-')) {
            throw new DoctestException(sprintf('Missing value for %s.', $option));
        }

        return $argv[$index + 1];
    }
}
