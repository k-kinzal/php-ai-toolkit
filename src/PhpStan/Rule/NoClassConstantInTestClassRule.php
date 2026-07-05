<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<\PhpParser\Node\Stmt\ClassConst>
 */
final class NoClassConstantInTestClassRule implements Rule
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
     * @return class-string<\PhpParser\Node\Stmt\ClassConst>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassConst::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassConst $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isRestrictedTestClass($scope)) {
            return [];
        }

        $names = array_map(
            static fn (\PhpParser\Node\Const_ $const): string => $const->name->toString(),
            $node->consts,
        );

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Inline class constant %s inside the test methods that use it. Tests\\Unit and Tests\\Integration classes must not declare constants.',
                    implode(', ', $names)
                )
            )
                ->identifier('customRules.testClassConstant')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
