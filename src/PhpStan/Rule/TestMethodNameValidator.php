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
                    'Test method test() must follow the pattern test[MethodName] or test[MethodName][Behavior]. The prefix "test" alone is not a valid name. Example: testUserCanLogin().'
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
                        'Test method %s() does not follow the naming convention. After the "test" prefix, the next character must be an uppercase letter (PascalCase). Example: testSomething(), testUserCanLogin().',
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
                        'Test method %s() tests a constructor or destructor directly. Constructors and destructors are implementation details; test the resulting behavior through the public API instead.',
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
