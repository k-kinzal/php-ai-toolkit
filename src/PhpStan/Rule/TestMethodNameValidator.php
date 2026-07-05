<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Validates PHPUnit test method naming conventions.
 */
final class TestMethodNameValidator
{
    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(string $methodName, int $line): array
    {
        $suffix = substr($methodName, 4);

        if ($suffix === '') {
            return [
                RuleErrorBuilder::message(
                    'Rename test() to test[MethodName] or test[MethodName][Behavior], e.g. testUserCanLogin().'
                )
                    ->identifier('customRules.testMethodNamingConvention')
                    ->line($line)
                    ->build(),
            ];
        }

        if (preg_match('/^[A-Z]$/', $suffix[0]) !== 1) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Rename test method %s() to use PascalCase after the "test" prefix, e.g. testSomething().',
                        $methodName
                    )
                )
                    ->identifier('customRules.testMethodNamingConvention')
                    ->line($line)
                    ->build(),
            ];
        }

        if (preg_match('/^(Construct|Destruct)/', $suffix) === 1) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Rename %s() and test behavior through the public API instead of targeting a constructor or destructor.',
                        $methodName
                    )
                )
                    ->identifier('customRules.testMethodProhibitedConstructorDestructor')
                    ->line($line)
                    ->build(),
            ];
        }

        return [];
    }
}
