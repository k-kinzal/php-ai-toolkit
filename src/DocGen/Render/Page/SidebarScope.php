<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

/**
 * Navigation scope of one rendered page.
 *
 * The sidebar shows the sections of the current page and the symbols that
 * sit next to it, so every page carries the package, the namespace, the
 * active symbol, and its own section anchors.
 *
 * @property-read ?string $packageName
 * @property-read ?string $namespace
 * @property-read ?string $activeFqcn
 * @property-read list<array{id: string, label: string, status?: string}> $sections
 */
final class SidebarScope
{
    /**
     * @param list<array{id: string, label: string, status?: string}> $sections
     */
    public function __construct(
        /** @readonly */
        private ?string $packageName,
        /** @readonly */
        private ?string $namespace,
        /** @readonly */
        private ?string $activeFqcn,
        /** @readonly */
        private array $sections,
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
            'packageName' => $this->packageName,
            'namespace' => $this->namespace,
            'activeFqcn' => $this->activeFqcn,
            'sections' => $this->sections,
            default => null,
        };
    }
}
