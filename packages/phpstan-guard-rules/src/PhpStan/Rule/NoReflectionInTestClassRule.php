<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Toolkit\PhpStan\Support\TestClassScope;

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
     * @return class-string<New_>
     */
    public function getNodeType(): string
    {
        return New_::class;
    }

    /**
     * @param New_ $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isTestClass($scope)) {
            return [];
        }

        if (!$node->class instanceof Name) {
            return [];
        }

        $className = $node->class->toString();

        if (!str_starts_with($className, 'Reflection')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Replace %s usage with assertions against public behavior. Test classes must not use Reflection.',
                    $className,
                )
            )
                ->identifier('customRules.noReflectionInTestClass')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
