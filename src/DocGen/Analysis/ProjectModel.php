<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis;

use Toolkit\DocGen\Analysis\Coverage\CoverageIndex;
use Toolkit\DocGen\Analysis\Layer\LayerModel;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\MarkdownDoc;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageGraph;

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
 * @property-read list<MarkdownDoc> $documents
 * @property-read ?string $baseUrl
 * @property-read ?string $repository
 */
final class ProjectModel
{
    /**
     * @param list<DiscoveredPackage> $packages
     * @param list<ClassLikeDoc> $classLikes
     * @param list<FunctionDoc> $functions
     * @param array<string, list<string>> $layerAssignments
     * @param list<string> $warnings
     * @param list<MarkdownDoc> $documents
     * @param ?string $baseUrl the address the site is published at, without a trailing slash, or null when it is unknown
     * @param ?string $repository the address of the repository the documented project lives in, or null when it names none
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
        /** @readonly */
        private array $documents = [],
        /** @readonly */
        private ?string $baseUrl = null,
        /** @readonly */
        private ?string $repository = null,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'title' => $this->title,
            'root' => $this->root,
            'packages' => $this->packages,
            'graph' => $this->graph,
            'classLikes' => $this->classLikes,
            'functions' => $this->functions,
            'symbolTable' => $this->symbolTable,
            'hierarchy' => $this->hierarchy,
            'usages' => $this->usages,
            'testCases' => $this->testCases,
            'layers' => $this->layers,
            'layerAssignments' => $this->layerAssignments,
            'coverage' => $this->coverage,
            'warnings' => $this->warnings,
            'documents' => $this->documents,
            'baseUrl' => $this->baseUrl,
            'repository' => $this->repository,
            default => null,
        };
    }
}
