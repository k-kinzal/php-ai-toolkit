<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Discovery;

/**
 * The discovered sources, packages, documents, and selection warnings.
 *
 * @property-read string $root
 * @property-read list<Package\DiscoveredPackage> $packages
 * @property-read list<SourceFile> $files
 * @property-read list<MarkdownDoc> $documents
 * @property-read list<string> $warnings
 */
final class SourceSet
{
    /**
     * Creates the discovered sources, packages, documents, and selection warnings.
     * @param list<Package\DiscoveredPackage> $packages
     * @param list<SourceFile> $files
     * @param list<MarkdownDoc> $documents
     * @param list<string> $warnings
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $packages,
        /** @readonly */
        private array $files,
        /** @readonly */
        private array $documents,
        /** @readonly */
        private array $warnings,
    ) {
    }

    /**
     * Provides read-only access to the stage's values.
     *
     * @return mixed the requested value
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'root' => $this->root,
            'packages' => $this->packages,
            'files' => $this->files,
            'documents' => $this->documents,
            'warnings' => $this->warnings,
            default => null,
        };
    }
}
