<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<\PhpParser\Node\Expr\New_>
 */
final class NoReflectionInTestClassRule implements Rule
{
    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        /** @readonly */
        private TestClassScope $testClassScope,
    ) {
    }

    /**
     * @return class-string<\PhpParser\Node\Expr\New_>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Expr\New_::class;
    }

    /**
     * @param \PhpParser\Node\Expr\New_ $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isTestClass($scope)) {
            return [];
        }

        if (!$node->class instanceof \PhpParser\Node\Name) {
            return [];
        }

        $className = $node->class->toString();

        if (!str_starts_with($className, 'Reflection')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Using %s in test classes is prohibited. If you need Reflection to test something, it is a sign that you are not testing behavior. Redesign the code or the test to verify observable behavior instead.',
                    $className,
                )
            )
                ->identifier('customRules.noReflectionInTestClass')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
