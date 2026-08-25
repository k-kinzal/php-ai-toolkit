<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Diff;

use Toolkit\DocGen\Analysis\ProjectModel;

/**
 * One opened comparison of two revisions.
 *
 * The checkouts stay on disk for as long as the session is open, because
 * the pages read the base revision of a file while they are rendered.
 *
 * @property-read ProjectModel $model
 * @property-read DiffIndex $diff
 * @property-read string $repositoryRoot
 * @property-read ?string $basePath
 * @property-read ?string $headPath
 */
final class DiffSession
{
    /**
     * Creates one opened comparison.
     */
    public function __construct(
        /** @readonly */
        private ProjectModel $model,
        /** @readonly */
        private DiffIndex $diff,
        /** @readonly */
        private string $repositoryRoot,
        /** @readonly */
        private ?string $basePath,
        /** @readonly */
        private ?string $headPath,
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
            'model' => $this->model,
            'diff' => $this->diff,
            'repositoryRoot' => $this->repositoryRoot,
            'basePath' => $this->basePath,
            'headPath' => $this->headPath,
            default => null,
        };
    }
}
