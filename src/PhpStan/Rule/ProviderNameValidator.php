<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Validates PHPUnit data provider naming conventions.
 */
final class ProviderNameValidator
{
    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(string $methodName, int $line): array
    {
        $suffix = substr($methodName, 8);

        if ($suffix === '') {
            return [
                RuleErrorBuilder::message(
                    'Rename provider() to provider[TestCaseName], e.g. providerValidEmails().'
                )
                    ->identifier('customRules.providerNamingConvention')
                    ->line($line)
                    ->build(),
            ];
        }

        if (preg_match('/^[A-Z]$/', $suffix[0]) !== 1) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Rename data provider %s() to use PascalCase after the "provider" prefix, e.g. providerValidEmails().',
                        $methodName
                    )
                )
                    ->identifier('customRules.providerNamingConvention')
                    ->line($line)
                    ->build(),
            ];
        }

        return [];
    }
}
