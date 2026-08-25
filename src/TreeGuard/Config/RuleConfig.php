<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Config;

/**
 * One tree.yaml rule block with a directory pattern and its constraints.
 *
 * Absent constraint keys are null and remain unchecked; an empty allow list
 * forbids every direct entry of the matched kind.
 *
 * @property-read string $path
 * @property-read ?int $maxFiles
 * @property-read ?int $maxDirs
 * @property-read ?int $maxTotalFiles
 * @property-read ?int $maxDepth
 * @property-read ?list<string> $allow
 * @property-read ?list<string> $deny
 * @property-read ?list<string> $allowDirs
 * @property-read ?list<string> $denyDirs
 * @property-read ?list<string> $require
 * @property-read bool $forbidEmpty
 * @property-read ?string $fileCase
 * @property-read ?string $dirCase
 */
final class RuleConfig
{
    /**
     * Creates one rule block from a directory pattern and optional constraints.
     *
     * @param ?list<string> $allow
     * @param ?list<string> $deny
     * @param ?list<string> $allowDirs
     * @param ?list<string> $denyDirs
     * @param ?list<string> $require
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private ?int $maxFiles,
        /** @readonly */
        private ?int $maxDirs,
        /** @readonly */
        private ?int $maxTotalFiles,
        /** @readonly */
        private ?int $maxDepth,
        /** @readonly */
        private ?array $allow,
        /** @readonly */
        private ?array $deny,
        /** @readonly */
        private ?array $allowDirs,
        /** @readonly */
        private ?array $denyDirs,
        /** @readonly */
        private ?array $require,
        /** @readonly */
        private bool $forbidEmpty,
        /** @readonly */
        private ?string $fileCase,
        /** @readonly */
        private ?string $dirCase,
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
            'path' => $this->path,
            'maxFiles' => $this->maxFiles,
            'maxDirs' => $this->maxDirs,
            'maxTotalFiles' => $this->maxTotalFiles,
            'maxDepth' => $this->maxDepth,
            'allow' => $this->allow,
            'deny' => $this->deny,
            'allowDirs' => $this->allowDirs,
            'denyDirs' => $this->denyDirs,
            'require' => $this->require,
            'forbidEmpty' => $this->forbidEmpty,
            'fileCase' => $this->fileCase,
            'dirCase' => $this->dirCase,
            default => null,
        };
    }
}
