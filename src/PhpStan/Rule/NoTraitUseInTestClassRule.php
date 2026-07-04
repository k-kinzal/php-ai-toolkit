<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<\PhpParser\Node\Stmt\TraitUse>
 */
final class NoTraitUseInTestClassRule implements Rule
{
    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        private readonly TestClassScope $testClassScope,
    ) {
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\TraitUse>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\TraitUse::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\TraitUse $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isRestrictedTestClass($scope)) {
            return [];
        }

        $names = array_map(
            static fn (\PhpParser\Node\Name $trait): string => $trait->toString(),
            $node->traits,
        );

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Trait %s is prohibited in Tests\\Unit and Tests\\Integration classes. Traits can circumvent test class restrictions (no properties, no constants, no private methods). Move shared behavior to a dedicated helper class and call it explicitly.',
                    implode(', ', $names)
                )
            )
                ->identifier('customRules.testClassTraitUse')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
