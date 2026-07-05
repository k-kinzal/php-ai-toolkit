<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Builds errors for direct magic method calls.
 */
final class MagicMethodCallErrorBuilder
{
    /** @readonly */
    private MagicMethodRegistry $magicMethodRegistry;

    /**
     * Creates a builder from magic method alternatives.
     */
    public function __construct(
        ?MagicMethodRegistry $magicMethodRegistry = null,
    ) {
        $this->magicMethodRegistry = $magicMethodRegistry ?? new MagicMethodRegistry();
    }

    /**
     * Builds the direct-call error.
     */
    public function error(string $methodName, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                '%s instead of calling %s() directly.',
                $this->magicMethodRegistry->alternative($methodName),
                $methodName
            )
        )
            ->identifier('customRules.forbiddenMagicMethodCall')
            ->line($line)
            ->build();
    }
}
