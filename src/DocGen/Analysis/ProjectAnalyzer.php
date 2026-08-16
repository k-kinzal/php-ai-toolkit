<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis;

use function array_merge;
use function basename;
use function fnmatch;
use function is_file;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex;
use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageReader;
use PhpAiToolkit\DocGen\Analysis\Document\DocumentCollector;
use PhpAiToolkit\DocGen\Analysis\Layer\DeptracConfigReader;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerAssigner;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerModel;
use PhpAiToolkit\DocGen\Analysis\Parse\ProjectSymbolCollector;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Cache\ParseCache;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageDiscovery;
use PhpAiToolkit\DocGen\Package\PackageGraphBuilder;

use function sprintf;

/**
 * Runs the full analysis pipeline from configuration to project model.
 */
final class ProjectAnalyzer
{
    /** @readonly */
    private PackageDiscovery $discovery;

    /** @readonly */
    private PackageGraphBuilder $graphBuilder;

    /** @readonly */
    private DocGenPathResolver $pathResolver;

    /** @readonly */
    private ProjectSymbolCollector $symbolCollector;

    /** @readonly */
    private DeptracConfigReader $deptracReader;

    /** @readonly */
    private LayerAssigner $layerAssigner;

    /** @readonly */
    private CoverageReader $coverageReader;

    /** @readonly */
    private DocumentCollector $documentCollector;

    /**
     * Creates a project analyzer from pipeline collaborators.
     */
    public function __construct(
        ?PackageDiscovery $discovery = null,
        ?PackageGraphBuilder $graphBuilder = null,
        ?DocGenPathResolver $pathResolver = null,
        ?ProjectSymbolCollector $symbolCollector = null,
        ?DeptracConfigReader $deptracReader = null,
        ?LayerAssigner $layerAssigner = null,
        ?CoverageReader $coverageReader = null,
        ?DocumentCollector $documentCollector = null,
    ) {
        $this->discovery = $discovery ?? new PackageDiscovery();
        $this->graphBuilder = $graphBuilder ?? new PackageGraphBuilder();
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
        $this->symbolCollector = $symbolCollector ?? new ProjectSymbolCollector();
        $this->deptracReader = $deptracReader ?? new DeptracConfigReader();
        $this->layerAssigner = $layerAssigner ?? new LayerAssigner();
        $this->coverageReader = $coverageReader ?? new CoverageReader();
        $this->documentCollector = $documentCollector ?? new DocumentCollector();
    }

    /**
     * Analyzes one configured project into its documentation model.
     *
     * @param ?int $workers how many workers to analyze with, or null for the default
     * @param ?ParseCache $cache what earlier runs already parsed, if it is kept
     *
     * @throws DocGenException when no package or source can be analyzed
     */
    public function analyze(DocGenConfig $config, ?int $workers = null, ?ParseCache $cache = null): ProjectModel
    {
        $packages = $this->discovery->discover($config);
        $collected = $this->symbolCollector->collect($config, $packages, $workers, $cache);

        $symbolTable = new SymbolTable();
        foreach ($collected['classLikes'] as $classLike) {
            $symbolTable->registerClassLike($classLike);
        }

        foreach ($collected['functions'] as $function) {
            $symbolTable->registerFunction($function);
        }

        $hierarchy = new HierarchyIndex();
        $hierarchy->build($collected['classLikes']);
        $usages = new UsageIndex();
        $usages->build($collected['usages']);
        $coverage = $this->coverageIndex($config);
        $testCases = new TestCaseIndex();
        $testCases->build($collected['usages'], $collected['classLikes'], $coverage);
        $layers = $this->layerModel($config);

        return new ProjectModel(
            $this->titleFor($config, $packages),
            $config->root,
            $packages,
            $this->graphBuilder->build($packages),
            $collected['classLikes'],
            $collected['functions'],
            $symbolTable,
            $hierarchy,
            $usages,
            $testCases,
            $layers,
            $this->layerAssignments($layers, $collected['classLikes']),
            $coverage,
            array_merge($this->vendorWarnings($config, $packages), $collected['warnings']),
            $this->documentCollector->collect($config, $packages),
            $config->baseUrl,
            $this->repositoryFor($config, $packages),
        );
    }

    /**
     * Determines the repository the documented project lives in.
     *
     * A project that configures an address means that one: it is the answer
     * where a repository has moved, where the manifest of the project says
     * nothing, and where the site is generated from a checkout that is not
     * the published one. Otherwise the root package answers for the project,
     * because a package already declares where its sources are browsable.
     *
     * @param list<DiscoveredPackage> $packages
     */
    public function repositoryFor(DocGenConfig $config, array $packages): ?string
    {
        if ($config->repository !== null) {
            return $config->repository;
        }

        foreach ($packages as $package) {
            if (!$package->isVendor && realpath($package->manifest->directory) === realpath($config->root)) {
                return $package->manifest->repository === '' ? null : $package->manifest->repository;
            }
        }

        return null;
    }

    /**
     * Warns about unusable vendor selections.
     *
     * Both the runtime globs of "vendor" and the dev globs of "vendor_dev" are
     * checked, and every selected package that ships no documentable source is
     * reported as well.
     *
     * @param list<DiscoveredPackage> $packages
     *
     * @return list<string>
     */
    public function vendorWarnings(DocGenConfig $config, array $packages): array
    {
        return array_merge(
            $this->vendorGlobWarnings($config->vendor, $packages, false),
            $this->vendorGlobWarnings($config->vendorDev, $packages, true),
            $this->vendorSourceWarnings($packages),
        );
    }

    /**
     * Warns about vendor globs that selected no package of one kind.
     *
     * Vendor globs match composer package names, so a directory name such
     * as "vendor" silently selects nothing without this warning. A glob also
     * selects nothing when it names a dev dependency while the runtime globs
     * are checked, or the other way round.
     *
     * @param list<string> $globs
     * @param list<DiscoveredPackage> $packages
     * @param bool $dev true when the dev globs are checked, false for runtime globs
     *
     * @return list<string>
     */
    public function vendorGlobWarnings(array $globs, array $packages, bool $dev): array
    {
        $warnings = [];
        foreach ($globs as $glob) {
            $matched = false;
            foreach ($packages as $package) {
                if ($package->isVendor && $package->isDevDependency === $dev && fnmatch($glob, $package->manifest->name)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $warnings[] = sprintf(
                    'Vendor glob "%s" documented no installed %s vendor package. Vendor globs match composer package names such as "acme/lib" or "acme/*", not directory names.',
                    $glob,
                    $dev ? 'dev' : 'runtime',
                );
            }
        }

        return $warnings;
    }

    /**
     * Warns about selected vendor packages without documentable sources.
     *
     * A package that autoloads only "files" entries, such as a phar bootstrap,
     * exposes no source directory to parse, so none of its classes can appear
     * in the site or be used as a link target.
     *
     * @param list<DiscoveredPackage> $packages
     *
     * @return list<string>
     */
    public function vendorSourceWarnings(array $packages): array
    {
        $warnings = [];
        foreach ($packages as $package) {
            if ($package->isVendor && $this->symbolCollector->sourceDirectories($package) === []) {
                $warnings[] = sprintf(
                    'Vendor package "%s" declares no PSR-4 or classmap autoload source, so its classes cannot be documented or linked. Packages that autoload only "files" entries, such as a phar bootstrap, cannot be documented: drop "%s" from the vendor globs.',
                    $package->manifest->name,
                    $package->manifest->name,
                );
            }
        }

        return $warnings;
    }

    /**
     * Assigns every class-like symbol to its deptrac layers.
     *
     * @param list<Model\ClassLikeDoc> $classLikes
     *
     * @return array<string, list<string>>
     */
    public function layerAssignments(?LayerModel $layers, array $classLikes): array
    {
        if ($layers === null) {
            return [];
        }

        $assignments = [];
        foreach ($classLikes as $classLike) {
            $names = $this->layerAssigner->assign($layers, $classLike);
            if ($names !== []) {
                $assignments[strtolower($classLike->fqcn)] = $names;
            }
        }

        return $assignments;
    }

    /**
     * Loads the deptrac layer model when a configuration is available.
     *
     * @throws DocGenException when a configured deptrac file is missing
     */
    public function layerModel(DocGenConfig $config): ?LayerModel
    {
        if ($config->deptrac !== null) {
            return $this->deptracReader->read($this->pathResolver->resolve($config->root, $config->deptrac));
        }

        $default = $config->root . '/deptrac.yaml';
        if (is_file($default)) {
            return $this->deptracReader->read($default);
        }

        return null;
    }

    /**
     * Loads the coverage index when a report directory is configured.
     *
     * @throws DocGenException when the configured report directory is missing
     */
    public function coverageIndex(DocGenConfig $config): ?CoverageIndex
    {
        if ($config->coverage === null) {
            return null;
        }

        return $this->coverageReader->read($this->pathResolver->resolve($config->root, $config->coverage), $config->root);
    }

    /**
     * Determines the site title from the configuration and packages.
     *
     * @param list<DiscoveredPackage> $packages
     */
    public function titleFor(DocGenConfig $config, array $packages): string
    {
        if ($config->title !== null) {
            return $config->title;
        }

        foreach ($packages as $package) {
            if (!$package->isVendor && realpath($package->manifest->directory) === realpath($config->root)) {
                return $package->manifest->name;
            }
        }

        return basename($config->root);
    }
}
