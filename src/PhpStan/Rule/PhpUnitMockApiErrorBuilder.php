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
                'Use createMock(FooInterface::class) or createStub(FooInterface::class) instead of PHPUnit %s().',
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
                'Pass an interface class-string literal to PHPUnit %s(), e.g. DependencyInterface::class. Do not pass variables or plain strings.',
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
                'Pass an interface class-string to PHPUnit %s(); "%s" is not an interface.',
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
                'Use createMock(FooInterface::class) or createStub(FooInterface::class) instead of instantiating %s directly.',
                $className
            )
        )
            ->identifier('customRules.testClassPhpUnitMockProhibitedInstantiation')
            ->line($line)
            ->build();
    }
}
