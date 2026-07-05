<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Builds PHPStan errors for prohibited PHPUnit mock APIs.
 */
final class PhpUnitMockApiErrorBuilder
{
    /**
     * Builds an error for APIs that are never allowed.
     */
    public function prohibitedApi(string $methodName, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'PHPUnit %s() is prohibited. Use createMock(FooInterface::class) or createStub(FooInterface::class) instead. These APIs enforce interface-based test doubles for better decoupling.',
                $methodName
            )
        )
            ->identifier('customRules.testClassPhpUnitMockProhibitedApi')
            ->line($line)
            ->build();
    }

    /**
     * Builds an error for missing direct interface class-string literals.
     */
    public function requiresLiteralInterface(string $methodName, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'PHPUnit %s() must use a direct interface class-string literal (e.g. DependencyInterface::class). Variables and string literals are not allowed because the type must be statically verifiable.',
                $methodName
            )
        )
            ->identifier('customRules.testClassPhpUnitMockRequiresLiteralInterface')
            ->line($line)
            ->build();
    }

    /**
     * Builds an error for class targets that are not interfaces.
     */
    public function requiresInterface(string $methodName, string $targetTypeName, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'PHPUnit %s() must target an interface; "%s" is not an interface. Mock only interfaces to keep tests decoupled from implementations.',
                $methodName,
                $targetTypeName
            )
        )
            ->identifier('customRules.testClassPhpUnitMockRequiresInterface')
            ->line($line)
            ->build();
    }

    /**
     * Builds an error for direct mock infrastructure instantiation.
     */
    public function prohibitedInstantiation(string $className, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Direct instantiation of %s is prohibited. Use createMock(FooInterface::class) or createStub(FooInterface::class) instead.',
                $className
            )
        )
            ->identifier('customRules.testClassPhpUnitMockProhibitedInstantiation')
            ->line($line)
            ->build();
    }
}
