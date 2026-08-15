<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function array_keys;
use function array_search;
use function explode;
use function in_array;
use function ksort;

use PhpAiToolkit\DocGen\Render\RenderKit;

use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
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

    /**
     * Lists the symbols declared directly in one namespace of a package.
     *
     * @return list<SymbolRow>
     */
    public function inNamespace(RenderKit $services, string $packageName, string $namespace): array
    {
        $rows = [];
        foreach ($services->model->classLikes as $classLike) {
            if ($classLike->packageName === $packageName && !$classLike->isDev && $classLike->namespace === $namespace) {
                $rows[] = new SymbolRow(
                    $classLike->kind,
                    $classLike->shortName,
                    $classLike->fqcn,
                    $services->url->classLikePage($classLike),
                    $classLike->docBlock !== null ? $classLike->docBlock->summary : '',
                    $services->model->layerAssignments[strtolower($classLike->fqcn)] ?? [],
                    $classLike->namespace,
                );
            }
        }

        foreach ($services->model->functions as $function) {
            if ($function->packageName === $packageName && !$function->isDev && $function->namespace === $namespace) {
                $rows[] = new SymbolRow(
                    'function',
                    $function->shortName,
                    $function->fqn,
                    $services->url->functionPage($function),
                    $function->docBlock !== null ? $function->docBlock->summary : '',
                    [],
                    $function->namespace,
                );
            }
        }

        return $this->sorted($rows);
    }

    /**
     * Lists every documented symbol of one package.
     *
     * @return list<SymbolRow>
     */
    public function inPackage(RenderKit $services, string $packageName): array
    {
        $rows = [];
        foreach ($this->namespacesOf($services, $packageName) as $namespace) {
            foreach ($this->inNamespace($services, $packageName, $namespace) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
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
        $namespaces = [];
        foreach ($services->model->classLikes as $classLike) {
            if ($classLike->packageName === $packageName && !$classLike->isDev) {
                $namespaces[$classLike->namespace] = true;
            }
        }

        foreach ($services->model->functions as $function) {
            if ($function->packageName === $packageName && !$function->isDev) {
                $namespaces[$function->namespace] = true;
            }
        }

        ksort($namespaces);

        return array_keys($namespaces);
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
