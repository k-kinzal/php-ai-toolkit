<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page;

use function array_keys;
use function array_search;
use function explode;
use function in_array;
use function ksort;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

use Toolkit\DocGen\Render\Page\Component\SymbolRow;
use Toolkit\DocGen\Render\RenderKit;

use function usort;

/**
 * Collects the documented symbols of a package for navigation listings.
 *
 * Listings are grouped by kind, with interfaces first, because the shape
 * of an API is usually looked up through its contracts.
 */
final class SymbolIndex
{
    /**
     * Kind order shared by every navigation and index listing.
     *
     * @var array<string, string>
     */
    public const KIND_LABELS = [
        'interface' => 'Interfaces',
        'class' => 'Classes',
        'trait' => 'Traits',
        'enum' => 'Enums',
        'function' => 'Functions',
    ];

    /**
     * Section anchor of every kind, used by listings and their links.
     *
     * @var array<string, string>
     */
    public const KIND_ANCHORS = [
        'interface' => 'interfaces',
        'class' => 'classes',
        'trait' => 'traits',
        'enum' => 'enums',
        'function' => 'functions',
    ];

    private ?RenderKit $run = null;

    /** @var array<string, list<SymbolRow>> */
    private array $rowsOfNamespace = [];

    /** @var array<string, list<SymbolRow>> */
    private array $rowsOfPackage = [];

    /** @var array<string, list<string>> */
    private array $namespacesOfPackage = [];

    /** @var array<string, list<string>> */
    private array $layersOfPackage = [];

    /** @var array<string, array<string, string>> */
    private array $layerStatusesOfPackage = [];

    /**
     * Remembers the listings of one render run.
     *
     * A listing is derived from the model and the comparison of one render
     * kit, and both stand still for the whole run, so the listing built for
     * the first page that asks answers every later page as well. This is
     * what keeps the sidebar of a site with hundreds of pages from scanning
     * the whole model once per page. A kit that differs from the remembered
     * one opens a new run and drops the listings of the previous one.
     */
    public function openRun(RenderKit $services): void
    {
        if ($this->run === $services) {
            return;
        }

        $this->run = $services;
        $this->rowsOfNamespace = [];
        $this->rowsOfPackage = [];
        $this->namespacesOfPackage = [];
        $this->layersOfPackage = [];
        $this->layerStatusesOfPackage = [];
    }

    /**
     * Builds the namespace listings of one package in a single pass.
     *
     * A site asks for the listing of every namespace it has, so scanning
     * the whole model once per namespace would cost the square of what a
     * project is worth. Every namespace of a package is therefore grouped
     * in one walk over the model, the first time any listing of that
     * package is asked for.
     */
    public function openPackage(RenderKit $services, string $packageName): void
    {
        $this->openRun($services);
        if (isset($this->namespacesOfPackage[$packageName])) {
            return;
        }

        $grouped = $this->classLikeRows($services, $packageName);
        foreach ($this->functionRows($services, $packageName) as $namespace => $rows) {
            foreach ($rows as $row) {
                $grouped[$namespace][] = $row;
            }
        }

        ksort($grouped);
        foreach ($grouped as $namespace => $rows) {
            $this->rowsOfNamespace[$packageName . "\n" . $namespace] = $this->sorted($rows);
        }

        $this->namespacesOfPackage[$packageName] = array_keys($grouped);
    }

    /**
     * Groups the documented class-like symbols of one package by namespace.
     *
     * @return array<string, list<SymbolRow>>
     */
    public function classLikeRows(RenderKit $services, string $packageName): array
    {
        $grouped = [];
        foreach ($services->model->classLikes as $classLike) {
            if ($classLike->packageName !== $packageName || $classLike->isDev
                || ($services->model->publicApi && !$services->model->isPublicApiClassLike($classLike->fqcn))) {
                continue;
            }

            $grouped[$classLike->namespace][] = new SymbolRow(
                $classLike->kind,
                $classLike->shortName,
                $classLike->fqcn,
                $services->url->classLikePage($classLike),
                $classLike->docBlock !== null ? $classLike->docBlock->summary : '',
                $services->model->layerAssignments[strtolower($classLike->fqcn)] ?? [],
                $classLike->namespace,
                $services->diff->classLikeStatus($classLike->fqcn),
                $classLike->docBlock !== null ? $classLike->docBlock->visibility : [],
            );
        }

        return $grouped;
    }

    /**
     * Groups the documented top-level functions of one package by namespace.
     *
     * @return array<string, list<SymbolRow>>
     */
    public function functionRows(RenderKit $services, string $packageName): array
    {
        $grouped = [];
        foreach ($services->model->functions as $function) {
            if ($function->packageName !== $packageName || $function->isDev
                || ($services->model->publicApi && !$services->model->isPublicApiFunction($function->fqn))) {
                continue;
            }

            $grouped[$function->namespace][] = new SymbolRow(
                'function',
                $function->shortName,
                $function->fqn,
                $services->url->functionPage($function),
                $function->docBlock !== null ? $function->docBlock->summary : '',
                [],
                $function->namespace,
                $services->diff->statusOf($services->diff->functionKey($function->fqn)),
                $function->docBlock !== null ? $function->docBlock->visibility : [],
            );
        }

        return $grouped;
    }

    /**
     * Lists the symbols declared directly in one namespace of a package.
     *
     * @return list<SymbolRow>
     */
    public function inNamespace(RenderKit $services, string $packageName, string $namespace): array
    {
        $this->openPackage($services, $packageName);

        return $this->rowsOfNamespace[$packageName . "\n" . $namespace] ?? [];
    }

    /**
     * Lists every documented symbol of one package.
     *
     * @return list<SymbolRow>
     */
    public function inPackage(RenderKit $services, string $packageName): array
    {
        $this->openPackage($services, $packageName);
        if (isset($this->rowsOfPackage[$packageName])) {
            return $this->rowsOfPackage[$packageName];
        }

        $rows = [];
        foreach ($this->namespacesOfPackage[$packageName] ?? [] as $namespace) {
            foreach ($this->rowsOfNamespace[$packageName . "\n" . $namespace] ?? [] as $row) {
                $rows[] = $row;
            }
        }

        return $this->rowsOfPackage[$packageName] = $rows;
    }

    /**
     * Lists the symbols of one package assigned to one architecture layer.
     *
     * @return list<SymbolRow>
     */
    public function inLayer(RenderKit $services, string $packageName, string $layer): array
    {
        $rows = [];
        foreach ($this->inPackage($services, $packageName) as $row) {
            if (in_array($layer, $row->layers, true)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Lists the architecture layers that hold symbols of one package.
     *
     * @return list<string>
     */
    public function layersOf(RenderKit $services, string $packageName): array
    {
        $rows = $this->inPackage($services, $packageName);
        if (isset($this->layersOfPackage[$packageName])) {
            return $this->layersOfPackage[$packageName];
        }

        $layers = [];
        foreach ($rows as $row) {
            foreach ($row->layers as $layer) {
                $layers[$layer] = true;
            }
        }

        ksort($layers);

        return $this->layersOfPackage[$packageName] = array_keys($layers);
    }

    /**
     * Combines the state of the symbols of every architecture layer.
     *
     * @return array<string, string>
     */
    public function layerStatuses(RenderKit $services, string $packageName): array
    {
        $rows = $this->inPackage($services, $packageName);
        if (isset($this->layerStatusesOfPackage[$packageName])) {
            return $this->layerStatusesOfPackage[$packageName];
        }

        $byLayer = [];
        foreach ($rows as $row) {
            foreach ($row->layers as $layer) {
                $byLayer[$layer][] = $row->status;
            }
        }

        $statuses = [];
        foreach ($byLayer as $layer => $layerStatuses) {
            $statuses[$layer] = $services->diff->combine($layerStatuses);
        }

        return $this->layerStatusesOfPackage[$packageName] = $statuses;
    }

    /**
     * Groups symbol rows by kind in the shared kind order.
     *
     * @param list<SymbolRow> $rows
     *
     * @return array<string, list<SymbolRow>>
     */
    public function byKind(array $rows): array
    {
        $groups = [];
        foreach (array_keys(self::KIND_LABELS) as $kind) {
            foreach ($rows as $row) {
                if ($row->kind === $kind) {
                    $groups[$kind][] = $row;
                }
            }
        }

        return $groups;
    }

    /**
     * Lists the namespaces of one package in sorted order.
     *
     * @return list<string>
     */
    public function namespacesOf(RenderKit $services, string $packageName): array
    {
        $this->openPackage($services, $packageName);

        return $this->namespacesOfPackage[$packageName] ?? [];
    }

    /**
     * Lists the direct child namespaces of one namespace.
     *
     * @return list<string>
     */
    public function childNamespaces(RenderKit $services, string $packageName, string $namespace): array
    {
        $prefix = $namespace === '' ? '' : $namespace . '\\';
        $children = [];
        foreach ($this->namespacesOf($services, $packageName) as $candidate) {
            if ($candidate === $namespace || !str_starts_with($candidate, $prefix)) {
                continue;
            }

            $children[$prefix . explode('\\', substr($candidate, strlen($prefix)))[0]] = true;
        }

        return array_keys($children);
    }

    /**
     * Sorts symbol rows by kind order and then by name.
     *
     * @param list<SymbolRow> $rows
     *
     * @return list<SymbolRow>
     */
    public function sorted(array $rows): array
    {
        $order = array_keys(self::KIND_LABELS);
        usort($rows, static function (SymbolRow $a, SymbolRow $b) use ($order): int {
            $kindOrder = array_search($a->kind, $order, true) <=> array_search($b->kind, $order, true);

            return $kindOrder !== 0 ? $kindOrder : $a->name <=> $b->name;
        });

        return $rows;
    }
}
