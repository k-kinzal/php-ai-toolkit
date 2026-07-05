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
                'Remove redundant assertInstanceOf(): "%s" is already an instance of "%s". Assert observable behavior instead.',
                $actualTypeName,
                $expectedTypeName,
            )
        )
            ->identifier('customRules.noRedundantAssertInstanceOf')
            ->line($line)
            ->build();
    }
}
