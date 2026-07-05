<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class NoHelperMethodInTestClassRule implements Rule
{
    private readonly TestMethodDetector $testMethodDetector;

    private readonly OverrideMethodDetector $overrideMethodDetector;

    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        private readonly TestClassScope $testClassScope,
        ?TestMethodDetector $testMethodDetector = null,
        ?OverrideMethodDetector $overrideMethodDetector = null,
    ) {
        $this->testMethodDetector = $testMethodDetector ?? new TestMethodDetector();
        $this->overrideMethodDetector = $overrideMethodDetector ?? new OverrideMethodDetector();
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
        if (!$this->testClassScope->isRestrictedTestClass($scope)) {
            return [];
        }

        $methodName = $node->name->toString();

        if ($this->testMethodDetector->isTestMethod($node)) {
            return [];
        }

        if (str_starts_with($methodName, 'provider')) {
            return [];
        }

        if ($this->overrideMethodDetector->isOverride($node, $scope)) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        $className = $classReflection !== null ? $classReflection->getName() : '(unknown)';

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Method %s() is not an override in %s. Test classes should only contain test methods and framework overrides. Move helper logic to a dedicated class or inline it into the test method.',
                    $methodName,
                    $className
                )
            )
                ->identifier('customRules.testClassNonOverrideMethod')
                ->line($node->getStartLine())
            ->build(),
        ];
    }
}
