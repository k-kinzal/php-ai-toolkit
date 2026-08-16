<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Package;

/**
 * Immutable view of one composer.json manifest.
 *
 * Autoload maps are normalized so every PSR-4 prefix maps to a list of
 * directories relative to the package directory. Classmap entries stay a plain
 * list of relative paths because composer allows both directories and single
 * files there.
 *
 * @property-read string $directory
 * @property-read string $name
 * @property-read string $description
 * @property-read array<string, list<string>> $autoload
 * @property-read array<string, list<string>> $devAutoload
 * @property-read array<string, string> $requires
 * @property-read array<string, string> $devRequires
 * @property-read array<string, string> $suggests
 * @property-read list<string> $classmap
 * @property-read list<string> $devClassmap
 */
final class ComposerManifest
{
    /**
     * @param array<string, list<string>> $autoload
     * @param array<string, list<string>> $devAutoload
     * @param array<string, string> $requires
     * @param array<string, string> $devRequires
     * @param array<string, string> $suggests
     * @param list<string> $classmap paths of the autoload.classmap section
     * @param list<string> $devClassmap paths of the autoload-dev.classmap section
     */
    public function __construct(
        /** @readonly */
        private string $directory,
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $description,
        /** @readonly */
        private array $autoload,
        /** @readonly */
        private array $devAutoload,
        /** @readonly */
        private array $requires,
        /** @readonly */
        private array $devRequires,
        /** @readonly */
        private array $suggests,
        /** @readonly */
        private array $classmap = [],
        /** @readonly */
        private array $devClassmap = [],
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
            'directory' => $this->directory,
            'name' => $this->name,
            'description' => $this->description,
            'autoload' => $this->autoload,
            'devAutoload' => $this->devAutoload,
            'requires' => $this->requires,
            'devRequires' => $this->devRequires,
            'suggests' => $this->suggests,
            'classmap' => $this->classmap,
            'devClassmap' => $this->devClassmap,
            default => null,
        };
    }
}
