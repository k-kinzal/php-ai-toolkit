<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse;

use function get_object_vars;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;

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
        return get_object_vars($this)[$name] ?? null;
    }
}
