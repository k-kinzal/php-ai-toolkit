<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Parse;

use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;

/**
 * All documented symbols collected from one source file.
 *
 * @property-read list<ClassLikeDoc> $classLikes
 * @property-read list<FunctionDoc> $functions
 */
final class FileSymbols
{
    /**
     * @param list<ClassLikeDoc> $classLikes
     * @param list<FunctionDoc> $functions
     */
    public function __construct(
        /** @readonly */
        private array $classLikes,
        /** @readonly */
        private array $functions,
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
            'classLikes' => $this->classLikes,
            'functions' => $this->functions,
            default => null,
        };
    }
}
