<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Builds PHPStan errors for redundant assertInstanceOf() calls.
 */
final class NoRedundantAssertInstanceOfErrorBuilder
{
    /**
     * Builds the error for a statically guaranteed instance assertion.
     */
    public function build(string $actualTypeName, string $expectedTypeName, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Redundant PHPUnit assertInstanceOf() in test class: the asserted value already has the statically-known type "%s", which is guaranteed to be an instance of "%s". Remove this assertion or replace it with an assertion about observable behavior.',
                $actualTypeName,
                $expectedTypeName,
            )
        )
            ->identifier('customRules.noRedundantAssertInstanceOf')
            ->line($line)
            ->build();
    }
}
