<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<\PhpParser\Node\Stmt\Property>
 */
final class NoPropertyInTestClassRule implements Rule
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
     * @return class-string<\PhpParser\Node\Stmt\Property>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\Property::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\Property $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isRestrictedTestClass($scope)) {
            return [];
        }

        $names = array_map(
            static fn (\PhpParser\Node\PropertyItem $prop): string => '$' . $prop->name->toString(),
            $node->props,
        );

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Property %s is prohibited in Tests\\Unit and Tests\\Integration classes. Shared state across test methods reduces test isolation and makes failures harder to debug. Declare values as local variables inside each test method instead.',
                    implode(', ', $names)
                )
            )
                ->identifier('customRules.testClassProperty')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
