<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Type;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * Builds actionable diagnostics for concrete mixed declarations.
 *
 * @visibility namespace
 */
final class MixedTypeErrorBuilder
{
    /**
     * Builds one declaration-specific error.
     */
    public function build(string $type, string $declaration, string $symbol, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Replace concrete mixed type "%s" in %s of %s: this declaration is internal or scope-restricted, so it must state a deterministic PHPStan type. Validate arbitrary input at an unrestricted public boundary, then pass the narrowed type inward.',
            $type,
            $declaration,
            $symbol
        ))
            ->identifier('customRules.internalMixedType')
            ->line($line)
            ->build();
    }
}
