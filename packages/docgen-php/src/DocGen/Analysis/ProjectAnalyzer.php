<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis;

use function array_merge;

use Toolkit\DocGen\Analysis\Coverage\CoverageIndex;
use Toolkit\DocGen\Analysis\Internal\Coverage\CoverageReader;
use Toolkit\DocGen\Analysis\Internal\Layer\DeptracConfigReader;
use Toolkit\DocGen\Analysis\Internal\Layer\LayerAssigner;
use Toolkit\DocGen\Analysis\Internal\Package\PackageGraphBuilder;
use Toolkit\DocGen\Analysis\Layer\LayerModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;

/**
 * Resolves project-wide relations from already extracted declarations and references.
 */
final class ProjectAnalyzer
{
    /** @readonly */
    private PackageGraphBuilder $graphBuilder;
    /** @readonly */
    private DeptracConfigReader $deptracReader;
    /** @readonly */
    private LayerAssigner $layerAssigner;
    /** @readonly */
    private CoverageReader $coverageReader;

    /**
     * Creates the readers and relation builders used by project analysis.
     */
    public function __construct(
        ?PackageGraphBuilder $graphBuilder = null,
        ?DeptracConfigReader $deptracReader = null,
        ?LayerAssigner $layerAssigner = null,
        ?CoverageReader $coverageReader = null,
    ) {
        $this->graphBuilder = $graphBuilder ?? new PackageGraphBuilder();
        $this->deptracReader = $deptracReader ?? new DeptracConfigReader();
        $this->layerAssigner = $layerAssigner ?? new LayerAssigner();
        $this->coverageReader = $coverageReader ?? new CoverageReader();
    }

    /**
     * Builds project relations from completed discovery and extraction.
     *
     * @throws DocGenException when an auxiliary report cannot be read
     */
    public function analyze(SourceSet $sources, ParsedProject $collected, AnalysisOptions $options): ProjectModel
    {

        $symbolTable = new SymbolTable();
        foreach ($collected->classLikes as $classLike) {
            $symbolTable->registerClassLike($classLike);
        }

        foreach ($collected->functions as $function) {
            $symbolTable->registerFunction($function);
        }

        $hierarchy = new HierarchyIndex();
        $hierarchy->build($collected->classLikes);
        $usages = new UsageIndex();
        $usages->build($collected->usages);
        $coverage = $this->coverageIndex($options->coverage, $sources->root);
        $testCases = new TestCaseIndex();
        $testCases->build($collected->usages, $collected->classLikes, $coverage);
        $layers = $this->layerModel($options->deptrac);

        return new ProjectModel(
            $options->title,
            $sources->root,
            $sources->packages,
            $this->graphBuilder->build($sources->packages),
            $collected->classLikes,
            $collected->functions,
            $symbolTable,
            $hierarchy,
            $usages,
            $testCases,
            $layers,
            $this->layerAssignments($layers, $collected->classLikes),
            $coverage,
            array_merge($sources->warnings, $collected->warnings),
            $sources->documents,
            $options->baseUrl,
            $options->repository,
            $options->publicApi,
        );
    }

    /**
     * Assigns every class-like symbol to its deptrac layers.
     *
     * @param list<ClassLikeDoc> $classLikes
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
     * Reads the selected dependency rules after source extraction.
     *
     * @throws DocGenException when the selected configuration is unreadable
     */
    public function layerModel(?string $path): ?LayerModel
    {
        return $path === null ? null : $this->deptracReader->read($path);
    }

    /**
     * Reads coverage information for the analyzed source root.
     *
     * @throws DocGenException when the selected report is unreadable
     */
    public function coverageIndex(?string $path, string $root): ?CoverageIndex
    {
        return $path === null ? null : $this->coverageReader->read($path, $root);
    }

}
