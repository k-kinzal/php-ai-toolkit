<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis;

use function get_object_vars;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerModel;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageGraph;

/**
 * The complete analyzed model of one documented project.
 *
 * @property-read string $title
 * @property-read string $root
 * @property-read list<DiscoveredPackage> $packages
 * @property-read PackageGraph $graph
 * @property-read list<ClassLikeDoc> $classLikes
 * @property-read list<FunctionDoc> $functions
 * @property-read SymbolTable $symbolTable
 * @property-read HierarchyIndex $hierarchy
 * @property-read UsageIndex $usages
 * @property-read TestCaseIndex $testCases
 * @property-read ?LayerModel $layers
 * @property-read array<string, list<string>> $layerAssignments
 * @property-read ?CoverageIndex $coverage
 * @property-read list<string> $warnings
 */
final class ProjectModel
{
    /**
     * @param list<DiscoveredPackage> $packages
     * @param list<ClassLikeDoc> $classLikes
     * @param list<FunctionDoc> $functions
     * @param array<string, list<string>> $layerAssignments
     * @param list<string> $warnings
     */
    public function __construct(
        /** @readonly */
        private string $title,
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $packages,
        /** @readonly */
        private PackageGraph $graph,
        /** @readonly */
        private array $classLikes,
        /** @readonly */
        private array $functions,
        /** @readonly */
        private SymbolTable $symbolTable,
        /** @readonly */
        private HierarchyIndex $hierarchy,
        /** @readonly */
        private UsageIndex $usages,
        /** @readonly */
        private TestCaseIndex $testCases,
        /** @readonly */
        private ?LayerModel $layers,
        /** @readonly */
        private array $layerAssignments,
        /** @readonly */
        private ?CoverageIndex $coverage,
        /** @readonly */
        private array $warnings,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return get_object_vars($this)[$name] ?? null;
    }
}
