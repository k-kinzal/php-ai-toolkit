<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Model;

/**
 * Enumerates the documented class-like kinds.
 *
 * A plain constant holder is used instead of a native enum to keep the
 * toolkit compatible with PHP 8.0.
 */
final class ClassLikeKind
{
    /**
     * Kind of a concrete or abstract class declaration.
     */
    public const CLASS_ = 'class';

    /**
     * Kind of an interface declaration.
     */
    public const INTERFACE_ = 'interface';

    /**
     * Kind of a trait declaration.
     */
    public const TRAIT_ = 'trait';

    /**
     * Kind of an enum declaration.
     */
    public const ENUM_ = 'enum';
}
