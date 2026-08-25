<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cli;

use function array_merge;
use function count;
use function explode;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;

use Toolkit\DocGen\Config\BaseUrl;
use Toolkit\DocGen\Config\RepositoryUrl;
use Toolkit\DocGen\DocGenException;

use function trim;

/**
 * Parses the doc-gen command line arguments.
 */
final class DocGenCliArgumentParser
{
    /**
     * The long options that carry a value, inline or as the next argument.
     *
     * @var list<string>
     */
    public const VALUE_OPTIONS = ['packages', 'exclude', 'output', 'title', 'deptrac', 'coverage', 'base-url', 'repository', 'memory-limit', 'jobs', 'diff', 'base', 'head', 'cache-dir'];

    /** @readonly */
    private BaseUrl $baseUrl;

    /** @readonly */
    private RepositoryUrl $repository;

    /**
     * Creates an argument parser from its value normalizers.
     */
    public function __construct(?BaseUrl $baseUrl = null, ?RepositoryUrl $repository = null)
    {
        $this->baseUrl = $baseUrl ?? new BaseUrl();
        $this->repository = $repository ?? new RepositoryUrl();
    }

    /**
     * Parses argument strings into a normalized option map.
     *
     * @param list<string> $argv
     *
     * @return array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool}
     *
     * @throws DocGenException when an option is unknown or lacks a value
     */
    public function parse(array $argv): array
    {
        $options = ['packages' => null, 'vendor' => null, 'vendorDev' => null, 'exclude' => null, 'output' => null, 'title' => null, 'deptrac' => null, 'coverage' => null, 'cacheDir' => null, 'baseUrl' => null, 'repository' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'noCache' => false, 'clearCache' => false, 'help' => false, 'version' => false];
        $count = count($argv);
        for ($index = 0; $index < $count; $index++) {
            $argument = $argv[$index];
            if ($argument === '--help' || $argument === '-h') {
                $options['help'] = true;
            } elseif ($argument === '--version' || $argument === '-V') {
                $options['version'] = true;
            } elseif ($argument === '--no-cache') {
                $options['noCache'] = true;
            } elseif ($argument === '--clear-cache') {
                $options['clearCache'] = true;
            } elseif ($argument === '--serve') {
                $options['serve'] = '127.0.0.1:8090';
            } elseif (str_starts_with($argument, '--serve=')) {
                $options['serve'] = $this->address(substr($argument, 8));
            } elseif ($argument === '--vendor' || str_starts_with($argument, '--vendor=')) {
                $options['vendor'] = $this->appendGlobs($options['vendor'], $this->vendorGlobs($argument, '--vendor'));
            } elseif ($argument === '--vendor-dev' || str_starts_with($argument, '--vendor-dev=')) {
                $options['vendorDev'] = $this->appendGlobs($options['vendorDev'], $this->vendorGlobs($argument, '--vendor-dev'));
            } elseif ($this->isValueOption($argument)) {
                $name = $this->optionName($argument);
                $options = $this->applyValueOption($options, $name, $this->take($argv, $index, $name));
                $index += $this->consumed($argument);
            } else {
                throw new DocGenException(sprintf('Unknown option: %s', $argument));
            }
        }

        return $this->validated($options);
    }

    /**
     * Reports whether an argument selects one of the value options.
     */
    public function isValueOption(string $argument): bool
    {
        foreach (self::VALUE_OPTIONS as $name) {
            if ($argument === '--' . $name || str_starts_with($argument, '--' . $name . '=')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the name of a long option, without its inline value.
     */
    public function optionName(string $argument): string
    {
        $name = substr($argument, 2);
        $position = strpos($name, '=');

        return $position === false ? $name : substr($name, 0, $position);
    }

    /**
     * Applies one value option to the option map.
     *
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool} $options
     *
     * @return array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool}
     *
     * @throws DocGenException when the value of the option is malformed
     */
    public function applyValueOption(array $options, string $name, string $value): array
    {
        if ($name === 'packages') {
            $options['packages'] = $this->appendGlobs($options['packages'], $this->globList($value, '--packages', 'directory glob'));
        } elseif ($name === 'exclude') {
            $options['exclude'] = $this->appendGlobs($options['exclude'], $this->globList($value, '--exclude', 'path glob'));
        } elseif ($name === 'output') {
            $options['output'] = $value;
        } elseif ($name === 'title') {
            $options['title'] = $value;
        } elseif ($name === 'deptrac') {
            $options['deptrac'] = $value;
        } elseif ($name === 'coverage') {
            $options['coverage'] = $value;
        } elseif ($name === 'base-url') {
            $options['baseUrl'] = $this->baseUrl->normalize($value);
        } elseif ($name === 'repository') {
            $options['repository'] = $this->repository->normalize($value);
        } else {
            $options = $this->applyRunOption($options, $name, $value);
        }

        return $options;
    }

    /**
     * Applies one option that decides how a run is carried out.
     *
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool} $options
     *
     * @return array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool}
     *
     * @throws DocGenException when the value of the option is malformed
     */
    public function applyRunOption(array $options, string $name, string $value): array
    {
        if ($name === 'memory-limit') {
            $options['memoryLimit'] = $this->memoryLimit($value);
        } elseif ($name === 'jobs') {
            $options['jobs'] = $this->jobs($value);
        } elseif ($name === 'cache-dir') {
            $options['cacheDir'] = $value;
        } elseif ($name === 'base') {
            $options['base'] = $value;
        } elseif ($name === 'head') {
            $options['head'] = $value;
        } else {
            $options = $this->revisionRange($options, $value);
        }

        return $options;
    }

    /**
     * Splits a BASE..HEAD range into the two compared revisions.
     *
     * A range without a head compares against the working tree, which is
     * what a reader looking at their own uncommitted change wants.
     *
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool} $options
     *
     * @return array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool}
     *
     * @throws DocGenException when the range names no base revision
     */
    public function revisionRange(array $options, string $value): array
    {
        $parts = explode('..', $value, 2);
        $base = trim($parts[0]);
        if ($base === '') {
            throw new DocGenException(sprintf(
                'Invalid --diff range: %s. Use BASE to compare against the working tree, or BASE..HEAD to compare two revisions.',
                $value,
            ));
        }

        $head = count($parts) === 2 ? trim($parts[1]) : '';
        $options['base'] = $base;
        $options['head'] = $head === '' ? $options['head'] : $head;

        return $options;
    }

    /**
     * Rejects the option combinations that cannot be acted on.
     *
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool} $options
     *
     * @return array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool}
     *
     * @throws DocGenException when a head revision has nothing to compare against
     */
    public function validated(array $options): array
    {
        if ($options['head'] !== null && $options['base'] === null) {
            throw new DocGenException('Option --head needs a revision to compare against: add --base=REVISION, or use --diff=BASE..HEAD.');
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

        return $this->globList(substr($argument, strlen($option) + 1), $option, 'package name glob');
    }

    /**
     * Adds the globs of one option occurrence to what earlier ones gave.
     *
     * A repeated list option adds to its list instead of replacing it, so a
     * command assembled from several places — a composer script and the CI
     * job that calls it — documents everything both of them named.
     *
     * @param ?list<string> $globs the globs given so far, or null for none
     * @param list<string> $more the globs of this occurrence
     *
     * @return list<string>
     */
    public function appendGlobs(?array $globs, array $more): array
    {
        return array_merge($globs ?? [], $more);
    }

    /**
     * Splits a comma-separated glob list.
     *
     * @param string $option the option name quoted in the error message
     * @param string $subject what one entry of the list is, such as "path glob"
     *
     * @return list<string>
     *
     * @throws DocGenException when the list is empty
     */
    public function globList(string $value, string $option, string $subject): array
    {
        $globs = [];
        foreach (explode(',', $value) as $glob) {
            $trimmed = trim($glob);
            if ($trimmed !== '') {
                $globs[] = $trimmed;
            }
        }

        if ($globs === []) {
            throw new DocGenException(sprintf('Option %s requires at least one %s.', $option, $subject));
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
     * Validates a worker count such as 4, or 1 for a sequential run.
     *
     * @throws DocGenException when the value is not a positive number
     */
    public function jobs(string $value): int
    {
        if (preg_match('/^[1-9]\d*$/', $value) !== 1) {
            throw new DocGenException(sprintf('Invalid --jobs value: %s. Use a worker count of 1 or more, or leave it out to use the cores of this machine.', $value));
        }

        return (int) $value;
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
