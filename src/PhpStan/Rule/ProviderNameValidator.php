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
                    'Data provider provider() must follow the pattern provider[TestCaseName]. The prefix "provider" alone is not a valid name. Example: providerValidEmails().'
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
                        'Data provider %s() does not follow the naming convention. After the "provider" prefix, the next character must be an uppercase letter (PascalCase). Example: providerValidEmails(), providerUserData().',
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
