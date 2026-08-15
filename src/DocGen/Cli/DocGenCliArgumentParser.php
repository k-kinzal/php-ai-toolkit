<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cli;

use function count;
use function explode;

use PhpAiToolkit\DocGen\DocGenException;

use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

/**
 * Parses the doc-gen command line arguments.
 */
final class DocGenCliArgumentParser
{
    /**
     * Parses argument strings into a normalized option map.
     *
     * @param list<string> $argv
     *
     * @return array{config: ?string, output: ?string, vendor: ?list<string>, vendorDev: ?list<string>, coverage: ?string, serve: ?string, memoryLimit: ?string, help: bool, version: bool}
     *
     * @throws DocGenException when an option is unknown or lacks a value
     */
    public function parse(array $argv): array
    {
        $options = ['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'serve' => null, 'memoryLimit' => null, 'help' => false, 'version' => false];
        $count = count($argv);
        for ($index = 0; $index < $count; $index++) {
            $argument = $argv[$index];
            if ($argument === '--help' || $argument === '-h') {
                $options['help'] = true;
            } elseif ($argument === '--version' || $argument === '-V') {
                $options['version'] = true;
            } elseif ($argument === '--serve') {
                $options['serve'] = '127.0.0.1:8090';
            } elseif (str_starts_with($argument, '--serve=')) {
                $options['serve'] = $this->address(substr($argument, 8));
            } elseif ($argument === '--vendor' || str_starts_with($argument, '--vendor=')) {
                $options['vendor'] = $this->vendorGlobs($argument, '--vendor');
            } elseif ($argument === '--vendor-dev' || str_starts_with($argument, '--vendor-dev=')) {
                $options['vendorDev'] = $this->vendorGlobs($argument, '--vendor-dev');
            } elseif ($this->valueOption($argument, 'config') !== null || $argument === '--config') {
                $options['config'] = $this->take($argv, $index, 'config');
                $index += $this->consumed($argument);
            } elseif ($this->valueOption($argument, 'output') !== null || $argument === '--output') {
                $options['output'] = $this->take($argv, $index, 'output');
                $index += $this->consumed($argument);
            } elseif ($this->valueOption($argument, 'coverage') !== null || $argument === '--coverage') {
                $options['coverage'] = $this->take($argv, $index, 'coverage');
                $index += $this->consumed($argument);
            } elseif ($this->valueOption($argument, 'memory-limit') !== null || $argument === '--memory-limit') {
                $options['memoryLimit'] = $this->memoryLimit($this->take($argv, $index, 'memory-limit'));
                $index += $this->consumed($argument);
            } else {
                throw new DocGenException(sprintf('Unknown option: %s', $argument));
            }
        }

        return $options;
    }

    /**
     * Returns the inline value of a long option, or null.
     */
    public function valueOption(string $argument, string $name): ?string
    {
        $prefix = '--' . $name . '=';
        if (str_starts_with($argument, $prefix)) {
            $value = substr($argument, strlen($prefix));

            return $value !== '' ? $value : null;
        }

        return null;
    }

    /**
     * Returns the value of an option at the current position.
     *
     * @param list<string> $argv
     *
     * @throws DocGenException when the option has no value
     */
    public function take(array $argv, int $index, string $name): string
    {
        $inline = $this->valueOption($argv[$index], $name);
        if ($inline !== null) {
            return $inline;
        }

        $next = $argv[$index + 1] ?? null;
        if ($next === null || str_starts_with($next, '-')) {
            throw new DocGenException(sprintf('Option --%s requires a value.', $name));
        }

        return $next;
    }

    /**
     * Returns how many extra arguments a value option consumed.
     */
    public function consumed(string $argument): int
    {
        return str_contains($argument, '=') ? 0 : 1;
    }

    /**
     * Returns the package name globs of a vendor option.
     *
     * The bare option without a value means every installed package, so it
     * expands to the match-all glob.
     *
     * @param string $argument the raw argument, such as --vendor-dev=acme/*
     * @param string $option the option name, such as --vendor-dev
     *
     * @return list<string>
     *
     * @throws DocGenException when the option has a value without any glob
     */
    public function vendorGlobs(string $argument, string $option): array
    {
        if ($argument === $option) {
            return ['*'];
        }

        return $this->globList(substr($argument, strlen($option) + 1), $option);
    }

    /**
     * Splits a comma-separated glob list.
     *
     * @param string $option the option name quoted in the error message
     *
     * @return list<string>
     *
     * @throws DocGenException when the list is empty
     */
    public function globList(string $value, string $option): array
    {
        $globs = [];
        foreach (explode(',', $value) as $glob) {
            $trimmed = trim($glob);
            if ($trimmed !== '') {
                $globs[] = $trimmed;
            }
        }

        if ($globs === []) {
            throw new DocGenException(sprintf('Option %s requires at least one package name glob.', $option));
        }

        return $globs;
    }

    /**
     * Validates a memory limit value such as 512M, 1G, or -1.
     *
     * @throws DocGenException when the value is malformed
     */
    public function memoryLimit(string $value): string
    {
        if (preg_match('/^(-1|\d+[KMG]?)$/i', $value) !== 1) {
            throw new DocGenException(sprintf('Invalid --memory-limit value: %s. Use a byte count, a value such as 512M or 1G, or -1 for no limit.', $value));
        }

        return $value;
    }

    /**
     * Normalizes a serve address, accepting a bare port number.
     *
     * @throws DocGenException when the address is malformed
     */
    public function address(string $value): string
    {
        if (preg_match('/^\d+$/', $value) === 1) {
            return '127.0.0.1:' . $value;
        }

        if (preg_match('/^[A-Za-z0-9_.\[\]-]+:\d+$/', $value) === 1) {
            return $value;
        }

        throw new DocGenException(sprintf('Invalid --serve address: %s. Use HOST:PORT or a port number.', $value));
    }
}
