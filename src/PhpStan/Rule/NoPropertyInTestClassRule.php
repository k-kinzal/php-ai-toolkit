<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule;

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
