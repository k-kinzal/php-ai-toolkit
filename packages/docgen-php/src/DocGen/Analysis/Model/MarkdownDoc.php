<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Model;

/**
 * One Markdown document that belongs to a documented package.
 *
 * The path is relative to the package directory and is what Markdown links
 * inside other documents resolve against; the file is relative to the
 * project root and is what the renderer reads the contents from.
 *
 * @property-read string $packageName
 * @property-read string $path
 * @property-read string $file
 * @property-read string $title
 */
final class MarkdownDoc
{
    /**
     * Creates one Markdown document model.
     */
    public function __construct(
        /** @readonly */
        private string $packageName,
        /** @readonly */
        private string $path,
        /** @readonly */
        private string $file,
        /** @readonly */
        private string $title,
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
            'path' => $this->path,
            'file' => $this->file,
            'title' => $this->title,
            default => null,
        };
    }
}
