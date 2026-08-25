<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Cli;

use PhpAiToolkit\ScopeGuard\ScopeGuardException;

use function sprintf;
use function str_starts_with;
use function substr;

/**
 * Parses ScopeGuard command-line arguments.
 *
 * @visibility namespace
 */
final class ScopeGuardCliArgumentParser
{
    /**
     * Parses ScopeGuard flags and options.
     *
     * @param list<string> $argv
     * @return array{config: string, help: bool, reporter: ?string, version: bool}
     *
     * @throws ScopeGuardException when an option is unknown or missing its value
     */
    public function parse(array $argv): array
    {
        $arguments = ['config' => 'scope.yaml', 'help' => false, 'reporter' => null, 'version' => false];
        $skipNext = false;

        foreach ($argv as $index => $arg) {
            if ($skipNext) {
                $skipNext = false;
                continue;
            }

            if ($arg === '--help' || $arg === '-h') {
                $arguments['help'] = true;
            } elseif ($arg === '--version' || $arg === '-V') {
                $arguments['version'] = true;
            } elseif ($arg === '--config') {
                if (!isset($argv[$index + 1]) || str_starts_with($argv[$index + 1], '-')) {
                    throw new ScopeGuardException(sprintf('Missing value for %s.', '--config'));
                }
                $arguments['config'] = $argv[$index + 1];
                $skipNext = true;
            } elseif (str_starts_with($arg, '--config=')) {
                $arguments['config'] = substr($arg, 9);
            } elseif ($arg === '--reporter' || $arg === '--format') {
                if (!isset($argv[$index + 1]) || str_starts_with($argv[$index + 1], '-')) {
                    throw new ScopeGuardException(sprintf('Missing value for %s.', $arg));
                }
                $arguments['reporter'] = $argv[$index + 1];
                $skipNext = true;
            } elseif (str_starts_with($arg, '--reporter=')) {
                $arguments['reporter'] = substr($arg, 11);
            } elseif (str_starts_with($arg, '--format=')) {
                $arguments['reporter'] = substr($arg, 9);
            } else {
                throw new ScopeGuardException(sprintf('Unknown option: %s', $arg));
            }
        }

        return $arguments;
    }
}
