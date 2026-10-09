<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cli;

use function array_merge;
use function count;
use function explode;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Toolkit\DocGen\Action\Config\BaseUrl;
use Toolkit\DocGen\Action\Config\DocGenConfig;
use Toolkit\DocGen\Action\Config\RepositoryUrl;
use Toolkit\DocGen\DocGenException;

use function trim;

/**
 * Declares the docgen command line options and reads them into an option map.
 *
 * Symfony Console splits the command line and rejects unknown options; this
 * class owns what the values mean and which of them cannot be acted on.
 */
final class DocGenCliArgumentParser
{
    /**
     * The address --serve listens on when it is given without one.
     */
    public const DEFAULT_SERVE_ADDRESS = '127.0.0.1:8090';

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
     * Returns the options of the docgen command, in the order help lists them.
     */
    public function definition(): InputDefinition
    {
        return new InputDefinition([
            new InputOption('packages', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Comma-separated directory globs probed for a composer.json; each match becomes a documented package', DocGenConfig::DEFAULT_PACKAGES),
            new InputOption('exclude', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Comma-separated path globs, relative to the project root, pruned from source scanning, such as tests/Fixture/*'),
            new InputOption('vendor', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Also document installed runtime vendor packages whose name matches a glob, such as acme/*; bare --vendor means all of them'),
            new InputOption('vendor-dev', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Also document installed dev vendor packages whose name matches a glob, such as phpunit/*; bare --vendor-dev means all of them'),
            new InputOption('output', 'o', InputOption::VALUE_REQUIRED, 'Directory the site is written to', DocGenConfig::DEFAULT_OUTPUT),
            new InputOption('title', null, InputOption::VALUE_REQUIRED, 'Site title (default: the name of the root package, else of the project directory)'),
            new InputOption('public-api', null, InputOption::VALUE_NONE, 'Publish only declarations marked @visibility public'),
            new InputOption('deptrac', null, InputOption::VALUE_REQUIRED, 'Deptrac configuration file the architecture graph and layer badges are read from (default: deptrac.yaml when it exists)'),
            new InputOption('coverage', null, InputOption::VALUE_REQUIRED, 'PHPUnit --coverage-xml report directory, used to link methods to the tests that cover them'),
            new InputOption('base-url', null, InputOption::VALUE_REQUIRED, 'Address the site is published at, such as https://example.github.io/project; adds canonical links and social preview tags'),
            new InputOption('repository', null, InputOption::VALUE_REQUIRED, 'Repository address every page links back to (default: support.source, then homepage, of the root package)'),
            new InputOption('diff', null, InputOption::VALUE_REQUIRED, 'Compare git revisions: BASE compares the working tree against BASE, BASE..HEAD compares two revisions'),
            new InputOption('base', null, InputOption::VALUE_REQUIRED, 'Base revision of the comparison; overrides the base of --diff'),
            new InputOption('head', null, InputOption::VALUE_REQUIRED, 'Head revision of the comparison (default: the working tree); overrides the head of --diff'),
            new InputOption('cache-dir', null, InputOption::VALUE_REQUIRED, 'Directory parsed sources and written pages are remembered in between runs', DocGenConfig::DEFAULT_CACHE),
            new InputOption('no-cache', null, InputOption::VALUE_NONE, 'Parse every source and write every page, and remember nothing of it'),
            new InputOption('clear-cache', null, InputOption::VALUE_NONE, 'Remove the cache directory before generating'),
            new InputOption('serve', null, InputOption::VALUE_OPTIONAL, 'Serve the generated site at HOST:PORT or a port after generation (default address: ' . self::DEFAULT_SERVE_ADDRESS . ')'),
            new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit of the run, such as 1G or -1 (default: the environment limit, raised to ' . DocGenMemoryLimit::FLOOR . ')'),
            new InputOption('jobs', 'j', InputOption::VALUE_REQUIRED, 'Number of worker processes (default: one per CPU core minus one, at most 16); 1 stays in one process'),
        ]);
    }

    /**
     * Parses raw arguments, as the shell passed them, into the option map.
     *
     * @param list<string> $argv the arguments without the executable name
     *
     * @return array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, publicApi: bool, noCache: bool, clearCache: bool}
     *
     * Symfony Console rejects an unknown option, an option without its
     * required value, and any argument.
     *
     * @throws DocGenException when an option value is malformed
     */
    public function parse(array $argv): array
    {
        return $this->read(new ArgvInput(array_merge(['docgen'], $argv), $this->definition()));
    }

    /**
     * Reads the options of bound input into a normalized option map.
     *
     * @return array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, publicApi: bool, noCache: bool, clearCache: bool}
     *
     * @throws DocGenException when an option value is malformed
     */
    public function read(InputInterface $input): array
    {
        $baseUrl = $this->text($input, 'base-url');
        $repository = $this->text($input, 'repository');
        $memoryLimit = $this->text($input, 'memory-limit');
        $jobs = $this->text($input, 'jobs');
        $options = [
            'packages' => $this->globs($input, 'packages', 'directory glob'),
            'vendor' => $this->vendorGlobs($input, 'vendor'),
            'vendorDev' => $this->vendorGlobs($input, 'vendor-dev'),
            'exclude' => $this->globs($input, 'exclude', 'path glob'),
            'output' => $this->text($input, 'output'),
            'title' => $this->text($input, 'title'),
            'deptrac' => $this->text($input, 'deptrac'),
            'coverage' => $this->text($input, 'coverage'),
            'cacheDir' => $this->text($input, 'cache-dir'),
            'baseUrl' => $baseUrl === null ? null : $this->baseUrl->normalize($baseUrl),
            'repository' => $repository === null ? null : $this->repository->normalize($repository),
            'serve' => $this->serve($input),
            'memoryLimit' => $memoryLimit === null ? null : $this->memoryLimit($memoryLimit),
            'jobs' => $jobs === null ? null : $this->jobs($jobs),
            'base' => null,
            'head' => null,
            'publicApi' => $input->getOption('public-api') === true,
            'noCache' => $input->getOption('no-cache') === true,
            'clearCache' => $input->getOption('clear-cache') === true,
        ];
        $diff = $this->text($input, 'diff');
        if ($diff !== null) {
            $options = $this->revisionRange($options, $diff);
        }

        $options['base'] = $this->text($input, 'base') ?? $options['base'];
        $options['head'] = $this->text($input, 'head') ?? $options['head'];

        return $this->validated($options);
    }

    /**
     * Returns the value of an option that takes one value, or null when absent.
     *
     * @throws DocGenException when the option was given an empty value
     */
    public function text(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || trim($value) === '') {
            throw new DocGenException(sprintf('Option --%s requires a value, such as --%s=VALUE.', $name, $name));
        }

        return $value;
    }

    /**
     * Returns the globs every occurrence of a list option named, or null when absent.
     *
     * A repeated list option adds to its list instead of replacing it, so a
     * command assembled from several places — a composer script and the CI
     * job that calls it — documents everything both of them named.
     *
     * @param string $subject what one entry of the list is, such as "path glob"
     *
     * @return ?list<string>
     *
     * @throws DocGenException when an occurrence names no glob
     */
    public function globs(InputInterface $input, string $name, string $subject): ?array
    {
        $globs = null;
        foreach ($this->values($input, $name) as $value) {
            $globs = array_merge($globs ?? [], $this->globList($value ?? '', '--' . $name, $subject));
        }

        return $globs;
    }

    /**
     * Returns the package name globs of a vendor option, or null when absent.
     *
     * The bare option without a value means every installed package, so it
     * expands to the match-all glob.
     *
     * @return ?list<string>
     *
     * @throws DocGenException when an occurrence has a value without any glob
     */
    public function vendorGlobs(InputInterface $input, string $name): ?array
    {
        $globs = null;
        foreach ($this->values($input, $name) as $value) {
            $more = $value === null ? ['*'] : $this->globList($value, '--' . $name, 'package name glob');
            $globs = array_merge($globs ?? [], $more);
        }

        return $globs;
    }

    /**
     * Returns every occurrence of an array option; a bare occurrence is null.
     *
     * @return list<?string>
     */
    public function values(InputInterface $input, string $name): array
    {
        $values = [];
        $option = $input->getOption($name);
        foreach (is_array($option) ? $option : [] as $value) {
            $values[] = is_string($value) ? $value : null;
        }

        return $values;
    }

    /**
     * Returns the address --serve asks for, or null when the site is not served.
     *
     * @throws DocGenException when the address is malformed
     */
    public function serve(InputInterface $input): ?string
    {
        $value = $input->getOption('serve');
        if (is_string($value)) {
            return $this->address($value);
        }

        return $input->hasParameterOption('--serve', true) ? self::DEFAULT_SERVE_ADDRESS : null;
    }

    /**
     * Splits a BASE..HEAD range into the two compared revisions.
     *
     * A range without a head compares against the working tree, which is
     * what a reader looking at their own uncommitted change wants.
     *
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, publicApi: bool, noCache: bool, clearCache: bool} $options
     *
     * @return array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, publicApi: bool, noCache: bool, clearCache: bool}
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
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, publicApi: bool, noCache: bool, clearCache: bool} $options
     *
     * @return array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, publicApi: bool, noCache: bool, clearCache: bool}
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
