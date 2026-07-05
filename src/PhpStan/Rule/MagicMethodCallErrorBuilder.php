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
    /**
     * Creates a builder from magic method alternatives.
     */
    public function __construct(
        private readonly MagicMethodRegistry $magicMethodRegistry = new MagicMethodRegistry(),
    ) {
    }

    /**
     * Builds the direct-call error.
     */
    public function error(string $methodName, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Direct call to magic method %s() is prohibited. Magic methods are invoked implicitly by PHP; calling them directly bypasses language semantics. %s.',
                $methodName,
                $this->magicMethodRegistry->alternative($methodName)
            )
        )
            ->identifier('customRules.forbiddenMagicMethodCall')
            ->line($line)
            ->build();
    }
}
