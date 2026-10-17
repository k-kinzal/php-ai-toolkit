<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Toolkit\PhpStan\Support\TestClassScope;

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
     * @return class-string<Property>
     */
    public function getNodeType(): string
    {
        return Property::class;
    }

    /**
     * @param Property $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isRestrictedTestClass($scope)) {
            return [];
        }

        $names = [];
        foreach ($node->props as $property) {
            $names[] = '$' . $property->name->toString();
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Move property %s into local variables inside the test methods that use it. Tests\\Unit and Tests\\Integration classes must not declare properties.',
                    implode(', ', $names)
                )
            )
                ->identifier('customRules.testClassProperty')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
