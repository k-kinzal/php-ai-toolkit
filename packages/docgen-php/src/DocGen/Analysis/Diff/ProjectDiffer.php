<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Diff;

use function array_merge;
use function strtolower;

use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Package\DiscoveredPackage;

/**
 * Diffs two analyzed revisions into one model the site is rendered from.
 *
 * The result is the head revision plus everything the head dropped, with
 * every element marked in the diff index. Rendering one model instead of
 * two sites is what lets a reader switch between the plain documentation
 * and the diff without leaving the page.
 */
final class ProjectDiffer
{
    /** @readonly */
    private ClassLikeMerger $classLikeMerger;

    /** @readonly */
    private FunctionMerger $functionMerger;

    /** @readonly */
    private DocumentDiffer $documents;

    /** @readonly */
    private DiffStatus $status;

    /**
     * Creates a project differ from its merging collaborators.
     */
    public function __construct(
        ?ClassLikeMerger $classLikeMerger = null,
        ?FunctionMerger $functionMerger = null,
        ?DocumentDiffer $documents = null,
        ?DiffStatus $status = null,
    ) {
        $this->classLikeMerger = $classLikeMerger ?? new ClassLikeMerger();
        $this->functionMerger = $functionMerger ?? new FunctionMerger();
        $this->documents = $documents ?? new DocumentDiffer();
        $this->status = $status ?? new DiffStatus();
    }

    /**
     * Diffs two project models into the merged model of the site.
     */
    public function diff(ProjectModel $base, ProjectModel $head, DiffIndex $index): ProjectModel
    {
        $classLikes = $this->classLikes($base, $head, $index);
        $functions = $this->functions($base, $head, $index);
        $packages = $this->packages($base, $head);
        $symbolTable = new SymbolTable();
        foreach ($classLikes as $classLike) {
            $symbolTable->registerClassLike($classLike);
        }

        foreach ($functions as $function) {
            $symbolTable->registerFunction($function);
        }

        $hierarchy = new HierarchyIndex();
        $hierarchy->build($classLikes);
        $this->markScopes($classLikes, $functions, $index);

        return new ProjectModel(
            $head->title,
            $head->root,
            $packages,
            $head->graph,
            $classLikes,
            $functions,
            $symbolTable,
            $hierarchy,
            $head->usages,
            $head->testCases,
            $head->layers,
            array_merge($base->layerAssignments, $head->layerAssignments),
            $head->coverage,
            $head->warnings,
            $this->documents->merge($base, $head, $index),
            $head->baseUrl,
            $head->repository,
            $head->publicApi,
            array_merge($base->publicApiClassLikes(), $head->publicApiClassLikes()),
            array_merge($base->publicApiFunctions(), $head->publicApiFunctions()),
        );
    }

    /**
     * Merges the class-like symbols of both revisions.
     *
     * @return list<ClassLikeDoc>
     */
    public function classLikes(ProjectModel $base, ProjectModel $head, DiffIndex $index): array
    {
        $baseByName = [];
        foreach ($base->classLikes as $classLike) {
            $baseByName[strtolower($classLike->fqcn)] = $classLike;
        }

        $merged = [];
        $seen = [];
        foreach ($head->classLikes as $classLike) {
            $key = strtolower($classLike->fqcn);
            $seen[$key] = true;
            $counterpart = $baseByName[$key] ?? null;
            $merged[] = $counterpart === null
                ? $this->classLikeMerger->single($classLike, DiffStatus::ADDED, $index)
                : $this->classLikeMerger->merge($counterpart, $classLike, $index);
        }

        foreach ($baseByName as $key => $classLike) {
            if (!isset($seen[$key])) {
                $merged[] = $this->classLikeMerger->single($classLike, DiffStatus::REMOVED, $index);
            }
        }

        return $merged;
    }

    /**
     * Merges the top-level functions of both revisions.
     *
     * @return list<FunctionDoc>
     */
    public function functions(ProjectModel $base, ProjectModel $head, DiffIndex $index): array
    {
        $baseByName = [];
        foreach ($base->functions as $function) {
            $baseByName[strtolower($function->fqn)] = $function;
        }

        $merged = [];
        $seen = [];
        foreach ($head->functions as $function) {
            $key = strtolower($function->fqn);
            $seen[$key] = true;
            $counterpart = $baseByName[$key] ?? null;
            $merged[] = $counterpart === null
                ? $this->functionMerger->single($function, DiffStatus::ADDED, $index)
                : $this->functionMerger->merge($counterpart, $function, $index);
        }

        foreach ($baseByName as $key => $function) {
            if (!isset($seen[$key])) {
                $merged[] = $this->functionMerger->single($function, DiffStatus::REMOVED, $index);
            }
        }

        return $merged;
    }

    /**
     * Merges the documented packages of both revisions.
     *
     * @return list<DiscoveredPackage>
     */
    public function packages(ProjectModel $base, ProjectModel $head): array
    {
        $packages = [];
        $seen = [];
        foreach ($head->packages as $package) {
            $seen[$package->manifest->name] = true;
            $packages[] = $package;
        }

        foreach ($base->packages as $package) {
            if (!isset($seen[$package->manifest->name])) {
                $packages[] = $package;
            }
        }

        return $packages;
    }

    /**
     * Records the state of every package and namespace of the site.
     *
     * A scope is as changed as the symbols it holds, which is what lets the
     * navigation hide the parts of the tree a revision never touched.
     *
     * @param list<ClassLikeDoc> $classLikes
     * @param list<FunctionDoc> $functions
     */
    public function markScopes(array $classLikes, array $functions, DiffIndex $index): void
    {
        $keys = $index->keys();
        $scopes = [];
        foreach ($classLikes as $classLike) {
            $status = $index->status($keys->classLike($classLike->fqcn));
            $scopes[$keys->package($classLike->packageName)][] = $status;
            $scopes[$keys->namespaceName($classLike->packageName, $classLike->namespace)][] = $status;
        }

        foreach ($functions as $function) {
            $status = $index->status($keys->functionSymbol($function->fqn));
            $scopes[$keys->package($function->packageName)][] = $status;
            $scopes[$keys->namespaceName($function->packageName, $function->namespace)][] = $status;
        }

        foreach ($scopes as $key => $statuses) {
            $index->mark($key, $this->status->combine($statuses));
        }
    }
}
