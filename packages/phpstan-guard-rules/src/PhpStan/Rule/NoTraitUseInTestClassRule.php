<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * @implements Rule<\PhpParser\Node\Stmt\TraitUse>
 */
final class NoTraitUseInTestClassRule implements Rule
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
     * @return class-string<TraitUse>
     */
    public function getNodeType(): string
    {
        return TraitUse::class;
    }

    /**
     * @param TraitUse $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isRestrictedTestClass($scope)) {
            return [];
        }

        $names = array_map(
            static fn (Name $trait): string => $trait->toString(),
            $node->traits,
        );

        if ($this->testClassScope->isPropertyTestClass($scope) && $node->adaptations === []) {
            $names = array_values(array_filter($names, static fn (string $name): bool => $name !== 'Eris\\TestTrait'));
            if ($names === []) {
                return [];
            }
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Move trait %s behavior to a dedicated collaborator and call it explicitly. Restricted test classes must not use traits.',
                    implode(', ', $names)
                )
            )
                ->identifier('customRules.testClassTraitUse')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
