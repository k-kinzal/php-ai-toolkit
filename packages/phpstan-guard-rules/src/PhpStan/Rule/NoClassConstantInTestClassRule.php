<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Stmt\ClassConst;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Toolkit\PhpStan\Support\TestClassScope;

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
     * @return class-string<ClassConst>
     */
    public function getNodeType(): string
    {
        return ClassConst::class;
    }

    /**
     * @param ClassConst $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isRestrictedTestClass($scope)) {
            return [];
        }

        $names = array_map(
            static fn (Const_ $const): string => $const->name->toString(),
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
