<?php

declare(strict_types=1);

namespace PhpStanAiRules\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpStanAiRules\Support\TestClassScope;

/**
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class NoPrivateMethodInTestClassRule implements Rule
{
    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        private readonly TestClassScope $testClassScope,
    ) {
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassMethod>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassMethod::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if (!$node->isPrivate()) {
            return [];
        }

        if (!$this->testClassScope->isRestrictedTestClass($scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Private method %s() is prohibited in Tests\\Unit and Tests\\Integration classes. Over-abstracted helpers hide test intent and make failures harder to understand. Inline the logic into each test method, or extract to a dedicated helper class if reuse is truly needed.',
                    $node->name->toString()
                )
            )
                ->identifier('customRules.testClassPrivateMethod')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
