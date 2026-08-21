<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\TestAssertion;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * Builds PHPStan errors for test cases that expect a failure of broken code.
 */
final class NoBrokenCodeExpectationErrorBuilder
{
    /**
     * Builds the error for an exception expectation that no working code can satisfy.
     */
    public function build(string $methodName, string $className, string $reason, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Delete this test case instead of expecting "%s" in %s(): %s. Keep only expectations for failures the code under test declares as behavior, such as a RuntimeException subclass.',
                $className,
                $methodName,
                $reason,
            )
        )
            ->identifier('customRules.noBrokenCodeExpectation')
            ->line($line)
            ->build();
    }
}
