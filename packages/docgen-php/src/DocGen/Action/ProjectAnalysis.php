<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Action;

use function basename;
use function is_file;

use Toolkit\DocGen\Action\Config\DocGenConfig;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\SourceDiscovery;
use Toolkit\DocGen\Discovery\SourceSelection;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Parse\Cache\ParseCache;
use Toolkit\DocGen\Parse\ProjectSymbolCollector;

/**
 * Runs the full analysis pipeline from configuration to project model.
 */
final class ProjectAnalysis
{
    /** @readonly */
    private SourceDiscovery $discovery;
    /** @readonly */
    private ProjectSymbolCollector $parser;
    /** @readonly */
    private ProjectAnalyzer $analyzer;
    /** @readonly */
    private DocGenPathResolver $pathResolver;

    /**
     * Creates the stages of one project analysis.
     */
    public function __construct(
        ?SourceDiscovery $discovery = null,
        ?ProjectSymbolCollector $parser = null,
        ?ProjectAnalyzer $analyzer = null,
        ?DocGenPathResolver $pathResolver = null,
    ) {
        $this->discovery = $discovery ?? new SourceDiscovery();
        $this->parser = $parser ?? new ProjectSymbolCollector();
        $this->analyzer = $analyzer ?? new ProjectAnalyzer();
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
    }

    /**
     * Runs discovery, extraction, and relation analysis in that order.
     *
     * @throws DocGenException when the selected project cannot be documented
     */
    public function analyze(DocGenConfig $config, ?int $workers = null, ?ParseCache $cache = null): ProjectModel
    {
        $sources = $this->discovery->discover(new SourceSelection(
            $config->root,
            $config->packages,
            $config->vendor,
            $config->exclude,
            $config->vendorDev,
        ));
        $parsed = $this->parser->collect($sources, $workers, $cache);
        $deptrac = $config->deptrac === null ? null : $this->pathResolver->resolve($config->root, $config->deptrac);
        if ($deptrac === null && is_file($config->root . '/deptrac.yaml')) {
            $deptrac = $config->root . '/deptrac.yaml';
        }

        $options = new AnalysisOptions(
            $this->titleFor($config, $sources->packages),
            $deptrac,
            $config->coverage === null ? null : $this->pathResolver->resolve($config->root, $config->coverage),
            $config->baseUrl,
            $this->repositoryFor($config, $sources->packages),
            $config->publicApi,
        );

        return $this->analyzer->analyze($sources, $parsed, $options);
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
