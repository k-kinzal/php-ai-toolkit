<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse;

use function file_get_contents;
use function filesize;
use function is_array;
use function is_dir;
use function is_string;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageCollector;
use PhpAiToolkit\DocGen\Cache\ParseCache;
use PhpAiToolkit\DocGen\Cache\SourceFileKey;
use PhpAiToolkit\DocGen\Cache\ToolkitFingerprint;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Filesystem\SourceFileFinder;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Parallel\WorkerPool;
use PhpAiToolkit\DocGen\Parallel\WorkScheduler;
use PhpParser\NodeTraverser;

use function sprintf;
use function strtolower;

/**
 * Parses the sources of every documented package into symbol lists.
 *
 * The files are parsed by worker processes, but the result is the result a
 * single process would have produced: the work is cut into consecutive
 * jobs, and the jobs are merged back in job order, so the order symbols and
 * references reach the site in never depends on how the work was divided.
 *
 * A file is parsed only when nothing about it is already known. Parsing one
 * file reads nothing but that file, so its symbols and references are the
 * same as long as the file, the package it belongs to, and the generator
 * are: what a previous run learned about an unchanged file is what this run
 * would learn again.
 */
final class ProjectSymbolCollector
{
    /** @readonly */
    private SourceFileFinder $fileFinder;

    /** @readonly */
    private DocGenPathResolver $pathResolver;

    /** @readonly */
    private AstParser $astParser;

    /** @readonly */
    private FileSymbolCollector $symbolCollector;

    /** @readonly */
    private WorkerPool $workers;

    /** @readonly */
    private WorkScheduler $scheduler;

    /** @readonly */
    private ToolkitFingerprint $fingerprint;

    /** @readonly */
    private SourceFileKey $fileKey;

    /**
     * Creates a project symbol collector from its parsing collaborators.
     */
    public function __construct(
        ?SourceFileFinder $fileFinder = null,
        ?DocGenPathResolver $pathResolver = null,
        ?AstParser $astParser = null,
        ?FileSymbolCollector $symbolCollector = null,
        ?WorkerPool $workers = null,
        ?WorkScheduler $scheduler = null,
        ?ToolkitFingerprint $fingerprint = null,
        ?SourceFileKey $fileKey = null,
    ) {
        $this->fileFinder = $fileFinder ?? new SourceFileFinder();
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
        $this->astParser = $astParser ?? new AstParser();
        $this->symbolCollector = $symbolCollector ?? new FileSymbolCollector();
        $this->workers = $workers ?? new WorkerPool();
        $this->scheduler = $scheduler ?? new WorkScheduler();
        $this->fingerprint = $fingerprint ?? new ToolkitFingerprint();
        $this->fileKey = $fileKey ?? new SourceFileKey();
    }

    /**
     * Parses all package sources into deduplicated symbol lists.
     *
     * @param list<DiscoveredPackage> $packages
     * @param ?int $workers how many workers to use, or null for the default
     * @param ?ParseCache $cache what earlier runs already parsed, if it is kept
     *
     * @return array{classLikes: list<ClassLikeDoc>, functions: list<FunctionDoc>, warnings: list<string>, usages: list<Usage>}
     *
     * @throws DocGenException when a worker cannot finish its files
     */
    public function collect(DocGenConfig $config, array $packages, ?int $workers = null, ?ParseCache $cache = null): array
    {
        $files = $this->sourceFiles($config, $packages);
        $fingerprint = $this->fingerprint->value();

        return $this->merged($this->workers->map(
            $this->scheduler->plan($files, static fn (array $file): int => (int) @filesize($file['file']), $workers),
            fn (array $job): array => $this->parseJob($config, $job, $fingerprint, $cache),
        ), $cache);
    }

    /**
     * Lists every source file to parse, in discovery order and once each.
     *
     * @param list<DiscoveredPackage> $packages
     *
     * @return list<array{package: DiscoveredPackage, source: array{directory: string, isDev: bool}, file: string}>
     */
    public function sourceFiles(DocGenConfig $config, array $packages): array
    {
        $found = [];
        $seenFiles = [];
        foreach ($packages as $package) {
            foreach ($this->sourceDirectories($package) as $source) {
                foreach ($this->fileFinder->find($source['directory'], $config->root, $config->exclude) as $file) {
                    if (isset($seenFiles[$file])) {
                        continue;
                    }

                    $seenFiles[$file] = true;
                    $found[] = ['package' => $package, 'source' => $source, 'file' => $file];
                }
            }
        }

        return $found;
    }

    /**
     * Reads the consecutive files of one job.
     *
     * @param list<array{package: DiscoveredPackage, source: array{directory: string, isDev: bool}, file: string}> $job
     * @param string $fingerprint the fingerprint of the generator
     *
     * @return list<array{cached: bool, symbols: FileSymbols|string, usages: list<Usage>}>
     */
    public function parseJob(DocGenConfig $config, array $job, string $fingerprint, ?ParseCache $cache = null): array
    {
        $parsed = [];
        foreach ($job as $file) {
            $parsed[] = $this->collectFile($config, $file['package'], $file['source'], $file['file'], $fingerprint, $cache);
        }

        return $parsed;
    }

    /**
     * Merges the job results into the deduplicated lists of the project.
     *
     * Whether a file was parsed or read back is counted here rather than
     * where it happened, because it happened in a worker process whose
     * counters end with it.
     *
     * @param list<mixed> $results what every job reported, in job order
     *
     * @return array{classLikes: list<ClassLikeDoc>, functions: list<FunctionDoc>, warnings: list<string>, usages: list<Usage>}
     *
     * @throws DocGenException when a job reported something else
     */
    public function merged(array $results, ?ParseCache $cache = null): array
    {
        $classLikes = [];
        $functions = [];
        $warnings = [];
        $usages = [];
        $seenSymbols = [];
        foreach ($results as $result) {
            foreach ($this->parsed($result) as $file) {
                $cache?->counted($file['cached']);

                foreach ($file['usages'] as $usage) {
                    $usages[] = $usage;
                }

                $symbols = $file['symbols'];
                if (is_string($symbols)) {
                    $warnings[] = $symbols;
                    continue;
                }

                foreach ($symbols->classLikes as $classLike) {
                    if (!isset($seenSymbols[strtolower($classLike->fqcn)])) {
                        $seenSymbols[strtolower($classLike->fqcn)] = true;
                        $classLikes[] = $classLike;
                    }
                }

                foreach ($symbols->functions as $function) {
                    if (!isset($seenSymbols[strtolower($function->fqn) . '()'])) {
                        $seenSymbols[strtolower($function->fqn) . '()'] = true;
                        $functions[] = $function;
                    }
                }
            }
        }

        return ['classLikes' => $classLikes, 'functions' => $functions, 'warnings' => $warnings, 'usages' => $usages];
    }

    /**
     * Reads what one job reported back as the files it must have read.
     *
     * A job result comes from a worker process, so nothing about its shape
     * is guaranteed by the type system that produced it. Anything that is
     * not a parse result means the site would silently lose the files that
     * job held, which is worse than stopping.
     *
     * @param mixed $result
     *
     * @return list<array{cached: bool, symbols: FileSymbols|string, usages: list<Usage>}>
     *
     * @throws DocGenException when the job reported something else
     */
    public function parsed($result): array
    {
        if (!is_array($result)) {
            throw new DocGenException('A documentation worker reported no parsed sources.');
        }

        $files = [];
        foreach ($result as $entry) {
            $files[] = $this->parsedFile($entry);
        }

        return $files;
    }

    /**
     * Reads what one job reported about one file as the file it must be.
     *
     * @param mixed $entry
     *
     * @return array{cached: bool, symbols: FileSymbols|string, usages: list<Usage>}
     *
     * @throws DocGenException when the job reported something else
     */
    public function parsedFile($entry): array
    {
        if (!is_array($entry) || !isset($entry['cached'], $entry['symbols'], $entry['usages'])
            || !is_bool($entry['cached']) || !is_array($entry['usages'])) {
            throw new DocGenException('A documentation worker reported no parsed sources.');
        }

        $symbols = $entry['symbols'];
        if (!is_string($symbols) && !$symbols instanceof FileSymbols) {
            throw new DocGenException('A documentation worker reported an unreadable source file.');
        }

        $usages = [];
        foreach ($entry['usages'] as $usage) {
            if (!$usage instanceof Usage) {
                throw new DocGenException('A documentation worker reported an unreadable symbol reference.');
            }

            $usages[] = $usage;
        }

        return ['cached' => $entry['cached'], 'symbols' => $symbols, 'usages' => $usages];
    }

    /**
     * Reads one source file, from the cache when it is already known.
     *
     * A file that was parsed is remembered here, in the worker that
     * parsed it, so filling the cache is as parallel as parsing is.
     *
     * @param array{directory: string, isDev: bool} $source
     *
     * @return array{cached: bool, symbols: FileSymbols|string, usages: list<Usage>}
     */
    public function collectFile(DocGenConfig $config, DiscoveredPackage $package, array $source, string $file, string $fingerprint, ?ParseCache $cache = null): array
    {
        $code = file_get_contents($file);
        if ($code === false) {
            return ['cached' => false, 'symbols' => sprintf('Skipped unreadable file: %s', $file), 'usages' => []];
        }

        $relative = $this->pathResolver->relative($config->root, $file);
        $key = $this->fileKey->of($fingerprint, $code, $relative, $package->manifest->name, $source['isDev']);
        $remembered = $cache === null ? null : $cache->find($key);
        if ($remembered !== null) {
            return ['cached' => true, 'symbols' => $remembered['symbols'], 'usages' => $remembered['usages']];
        }

        $parsed = $this->parseFile($code, $relative, $package->manifest->name, $source['isDev']);
        $cache?->remember($key, $parsed['symbols'], $parsed['usages']);

        return ['cached' => false, 'symbols' => $parsed['symbols'], 'usages' => $parsed['usages']];
    }

    /**
     * Parses one source file into its symbols and its references.
     *
     * @return array{symbols: FileSymbols|string, usages: list<Usage>} the symbols, or a warning message
     */
    public function parseFile(string $code, string $relative, string $packageName, bool $isDev): array
    {
        try {
            $statements = $this->astParser->parse($code, $relative);
        } catch (DocGenException $exception) {
            return ['symbols' => $exception->getMessage(), 'usages' => []];
        }

        $symbols = $this->symbolCollector->collect($statements, $packageName, $relative, $isDev);
        $collector = new UsageCollector();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $collector->beginFile($relative, $isDev);
        $traverser->traverse($statements);

        return ['symbols' => $symbols, 'usages' => $collector->usages()];
    }

    /**
     * Lists the autoload source directories of one package.
     *
     * PSR-4 prefixes always map to directories and are taken as declared; an
     * empty prefix path is the package root. Classmap entries may name a
     * directory or a single file, so only the entries that exist as a
     * directory are kept; single classmap files are not documented.
     *
     * @return list<array{directory: string, isDev: bool}>
     */
    public function sourceDirectories(DiscoveredPackage $package): array
    {
        $sources = [];
        foreach ([['map' => $package->manifest->autoload, 'isDev' => false], ['map' => $package->manifest->devAutoload, 'isDev' => true]] as $section) {
            foreach ($section['map'] as $directories) {
                foreach ($directories as $directory) {
                    $sources[] = [
                        'directory' => $directory === '' ? $package->manifest->directory : $this->pathResolver->resolve($package->manifest->directory, $directory),
                        'isDev' => $section['isDev'],
                    ];
                }
            }
        }

        foreach ([['paths' => $package->manifest->classmap, 'isDev' => false], ['paths' => $package->manifest->devClassmap, 'isDev' => true]] as $section) {
            foreach ($section['paths'] as $path) {
                $directory = $this->pathResolver->resolve($package->manifest->directory, $path);
                if (is_dir($directory)) {
                    $sources[] = ['directory' => $directory, 'isDev' => $section['isDev']];
                }
            }
        }

        return $sources;
    }
}
