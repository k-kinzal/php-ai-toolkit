<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page\Component;

use function sprintf;

use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;

/**
 * Says in one sentence what one documented symbol is.
 *
 * This is what a page is previewed by rather than what it is read by: the
 * summary line its author wrote is the sentence, and a symbol documented
 * with nothing is named by what it is and where it lives, so a shared link
 * is never previewed by an empty description.
 */
final class SymbolDescription
{
    /**
     * Describes one class, interface, trait, or enum.
     */
    public function ofClassLike(ClassLikeDoc $classLike): string
    {
        $summary = $classLike->docBlock !== null ? $classLike->docBlock->summary : '';
        if ($summary !== '') {
            return $summary;
        }

        return sprintf('The %s %s of the %s package.', $classLike->kind, $classLike->fqcn, $classLike->packageName);
    }

    /**
     * Describes one function.
     */
    public function ofFunction(FunctionDoc $function): string
    {
        $summary = $function->docBlock !== null ? $function->docBlock->summary : '';
        if ($summary !== '') {
            return $summary;
        }

        return sprintf('The %s() function of the %s package.', $function->fqn, $function->packageName);
    }
}
