<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Builds errors for public source methods without matching unit tests.
 */
final class PublicMethodTestCoverageErrorBuilder
{
    /**
     * Builds an error for a public method with no matching test method prefix.
     */
    public function build(string $methodName, string $expectedPrefix, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Add a unit test method starting with %s() for public method %s().',
                $expectedPrefix,
                $methodName
            )
        )
            ->identifier('customRules.publicMethodWithoutTest')
            ->line($line)
            ->build();
    }
}
