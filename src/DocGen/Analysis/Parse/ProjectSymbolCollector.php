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
    ) {
        $this->fileFinder = $fileFinder ?? new SourceFileFinder();
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
        $this->astParser = $astParser ?? new AstParser();
        $this->symbolCollector = $symbolCollector ?? new FileSymbolCollector();
        $this->workers = $workers ?? new WorkerPool();
        $this->scheduler = $scheduler ?? new WorkScheduler();
    }

    /**
     * Parses all package sources into deduplicated symbol lists.
     *
     * @param list<DiscoveredPackage> $packages
     * @param ?int $workers how many workers to use, or null for the default
     *
     * @return array{classLikes: list<ClassLikeDoc>, functions: list<FunctionDoc>, warnings: list<string>, usages: list<Usage>}
     *
     * @throws DocGenException when a worker cannot finish its files
     */
    public function collect(DocGenConfig $config, array $packages, ?int $workers = null): array
    {
        $files = $this->sourceFiles($config, $packages);

        return $this->merged($this->workers->map(
            $this->scheduler->plan($files, static fn (array $file): int => (int) @filesize($file['file']), $workers),
            fn (array $job): array => $this->parseJob($config, $job),
        ));
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
     * Parses the consecutive files of one job.
     *
     * One collector serves the whole job, as one collector served the whole
     * project before there were jobs. The usages it gathers stay in file
     * order, and the jobs themselves are consecutive, so concatenating the
     * jobs restores the order a single process would have produced.
     *
     * @param list<array{package: DiscoveredPackage, source: array{directory: string, isDev: bool}, file: string}> $job
     *
     * @return array{symbols: list<FileSymbols|string>, usages: list<Usage>}
     */
    public function parseJob(DocGenConfig $config, array $job): array
    {
        $collector = new UsageCollector();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $symbols = [];
        foreach ($job as $file) {
            $symbols[] = $this->collectFile($config, $file['package'], $file['source'], $file['file'], $collector, $traverser);
        }

        return ['symbols' => $symbols, 'usages' => $collector->usages()];
    }

    /**
     * Merges the job results into the deduplicated lists of the project.
     *
     * @param list<mixed> $results what every job reported, in job order
     *
     * @return array{classLikes: list<ClassLikeDoc>, functions: list<FunctionDoc>, warnings: list<string>, usages: list<Usage>}
     *
     * @throws DocGenException when a job reported something else
     */
    public function merged(array $results): array
    {
        $classLikes = [];
        $functions = [];
        $warnings = [];
        $usages = [];
        $seenSymbols = [];
        foreach ($results as $result) {
            $parsed = $this->parsed($result);
            foreach ($parsed['usages'] as $usage) {
                $usages[] = $usage;
            }

            foreach ($parsed['symbols'] as $symbols) {
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
     * Reads what one job reported back as the parse result it must be.
     *
     * A job result comes from a worker process, so nothing about its shape
     * is guaranteed by the type system that produced it. Anything that is
     * not a parse result means the site would silently lose the files that
     * job held, which is worse than stopping.
     *
     * @param mixed $result
     *
     * @return array{symbols: list<FileSymbols|string>, usages: list<Usage>}
     *
     * @throws DocGenException when the job reported something else
     */
    public function parsed($result): array
    {
        $symbols = [];
        $usages = [];
        if (!is_array($result) || !isset($result['symbols'], $result['usages']) || !is_array($result['symbols']) || !is_array($result['usages'])) {
            throw new DocGenException('A documentation worker reported no parsed sources.');
        }

        foreach ($result['symbols'] as $entry) {
            if (!is_string($entry) && !$entry instanceof FileSymbols) {
                throw new DocGenException('A documentation worker reported an unreadable source file.');
            }

            $symbols[] = $entry;
        }

        foreach ($result['usages'] as $usage) {
            if (!$usage instanceof Usage) {
                throw new DocGenException('A documentation worker reported an unreadable symbol reference.');
            }

            $usages[] = $usage;
        }

        return ['symbols' => $symbols, 'usages' => $usages];
    }

    /**
     * Parses one source file and feeds it into the usage collector.
     *
     * @param array{directory: string, isDev: bool} $source
     *
     * @return FileSymbols|string the symbols, or a warning message
     */
    public function collectFile(DocGenConfig $config, DiscoveredPackage $package, array $source, string $file, UsageCollector $usageCollector, NodeTraverser $traverser)
    {
        $code = file_get_contents($file);
        if ($code === false) {
            return sprintf('Skipped unreadable file: %s', $file);
        }

        $relative = $this->pathResolver->relative($config->root, $file);
        try {
            $statements = $this->astParser->parse($code, $relative);
        } catch (DocGenException $exception) {
            return $exception->getMessage();
        }

        $symbols = $this->symbolCollector->collect($statements, $package->manifest->name, $relative, $source['isDev']);
        $usageCollector->beginFile($relative, $source['isDev']);
        $traverser->traverse($statements);

        return $symbols;
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
