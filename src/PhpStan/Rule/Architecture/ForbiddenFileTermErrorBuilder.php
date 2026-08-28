<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * Builds design-oriented errors for forbidden terms found in restricted paths.
 */
final class ForbiddenFileTermErrorBuilder
{
    /**
     * Reports a concept that has leaked into a layer where it cannot belong.
     */
    public function build(string $term, string $pathPattern, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Forbidden term "%s" appears in a file matched by path "%s"; this is a design error because the concept does not belong in this layer. Redesign the responsibility boundary and move the concept and its behavior to the appropriate layer. Renaming, abbreviating, or deleting only the term is not a fix.',
                $term,
                $pathPattern
            )
        )
            ->identifier('customRules.forbiddenFileTerm')
            ->line($line)
            ->build();
    }
}
