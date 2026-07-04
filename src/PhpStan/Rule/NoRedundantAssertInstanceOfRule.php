<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<\PhpParser\Node\Expr>
 */
final class NoRedundantAssertInstanceOfRule implements Rule
{
    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        private readonly TestClassScope $testClassScope,
    ) {
    }

    /**
     * @return class-string<\PhpParser\Node\Expr>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Expr::class;
    }

    /**
     * @param \PhpParser\Node\Expr $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isTestClass($scope)) {
            return [];
        }

        if ($node instanceof \PhpParser\Node\Expr\MethodCall) {
            if (!$this->isCallOnThis($node)) {
                return [];
            }

            return $this->processCall(
                $this->resolveMethodName($node->name),
                $node->args,
                $node->getStartLine(),
                $scope,
            );
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticCall) {
            if (!$this->isStaticCallOnPhpUnitAssert($node, $scope)) {
                return [];
            }

            return $this->processCall(
                $this->resolveMethodName($node->name),
                $node->args,
                $node->getStartLine(),
                $scope,
            );
        }

        return [];
    }

    /**
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Expr $name
     */
    private function resolveMethodName($name): ?string
    {
        if (!$name instanceof \PhpParser\Node\Identifier) {
            return null;
        }

        return $name->toString();
    }

    /**
     * @param array<array-key, \PhpParser\Node\Arg|\PhpParser\Node\VariadicPlaceholder> $args
     * @return list<IdentifierRuleError>
     */
    private function processCall(?string $methodName, array $args, int $line, Scope $scope): array
    {
        if ($methodName !== 'assertInstanceOf') {
            return [];
        }

        $expectedExpression = $this->argValue($args, 0);
        $actualExpression = $this->argValue($args, 1);
        if ($expectedExpression === null || $actualExpression === null) {
            return [];
        }

        $expectedTypeName = $this->resolveTypeNameFromExpression($expectedExpression, $scope);
        if ($expectedTypeName === null) {
            return [];
        }

        $actualType = $scope->getType($actualExpression);
        $actualTypeNames = $actualType->getObjectClassNames();
        if (count($actualTypeNames) !== 1) {
            return [];
        }

        if (!(new ObjectType($expectedTypeName))->isSuperTypeOf($actualType)->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Redundant PHPUnit assertInstanceOf() in test class: the asserted value already has the statically-known type "%s", which is guaranteed to be an instance of "%s". Remove this assertion or replace it with an assertion about observable behavior.',
                    $actualTypeNames[0],
                    $expectedTypeName,
                )
            )
                ->identifier('customRules.noRedundantAssertInstanceOf')
                ->line($line)
                ->build(),
        ];
    }

    /**
     * @param array<array-key, \PhpParser\Node\Arg|\PhpParser\Node\VariadicPlaceholder> $args
     */
    private function argValue(array $args, int $position): ?\PhpParser\Node\Expr
    {
        $arg = $args[$position] ?? null;

        return $arg instanceof \PhpParser\Node\Arg ? $arg->value : null;
    }

    private function resolveTypeNameFromExpression(\PhpParser\Node\Expr $expression, Scope $scope): ?string
    {
        if (!$expression instanceof \PhpParser\Node\Expr\ClassConstFetch) {
            return null;
        }

        if (!$expression->name instanceof \PhpParser\Node\Identifier) {
            return null;
        }

        if ($expression->name->toString() !== 'class') {
            return null;
        }

        if (!$expression->class instanceof \PhpParser\Node\Name) {
            return null;
        }

        return $scope->resolveName($expression->class);
    }

    private function isCallOnThis(\PhpParser\Node\Expr\MethodCall $node): bool
    {
        return $node->var instanceof \PhpParser\Node\Expr\Variable
            && $node->var->name === 'this';
    }

    private function isStaticCallOnPhpUnitAssert(\PhpParser\Node\Expr\StaticCall $node, Scope $scope): bool
    {
        if (!$node->class instanceof \PhpParser\Node\Name) {
            return false;
        }

        $className = $node->class->toString();
        if (in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
            return true;
        }

        return in_array($scope->resolveName($node->class), [
            'PHPUnit\\Framework\\Assert',
            'PHPUnit\\Framework\\TestCase',
        ], true);
    }
}
