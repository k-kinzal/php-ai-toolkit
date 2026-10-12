<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Rector\Contract\Rector\RectorInterface;

/**
 * Groups transformations whose behavior or contract changes require review.
 */
final class RiskyRules
{
    /**
     * Returns only risky rule classes for explicit registration with Rector.
     *
     * @return list<class-string<RectorInterface>>
     */
    public static function rules(): array
    {
        return [
            ForbiddenCommentRector::class,
            ForbiddenMagicMethodCallRector::class,
            ForbidNonDocCommentRector::class,
            ForbidEmptyCatchRector::class,
            RequireExceptionChainingRector::class,
            RequireThrowsTagOnDirectThrowRector::class,
            RequireListForArrayLiteralRector::class,
            ForbidDescriptivePhpDocInTestClassRector::class,
        ];
    }
}
