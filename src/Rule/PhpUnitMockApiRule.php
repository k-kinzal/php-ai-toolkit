<?php

declare(strict_types=1);

namespace PhpStanAiRules\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\MockObject\Generator\Generator as MockGenerator;
use PHPUnit\Framework\MockObject\MockBuilder;
use PhpStanAiRules\Support\TestClassScope;

/**
 * @implements Rule<\PhpParser\Node\Expr>
 */
final class PhpUnitMockApiRule implements Rule
{
    /**
     * @var list<string>
     */
    private const ALWAYS_PROHIBITED_METHODS = [
        'getMockBuilder',
        'createPartialMock',
        'createTestProxy',
        'getMockForAbstractClass',
        'getMockForTrait',
        'getMockFromWsdl',
    ];

    /**
     * @var list<string>
     */
    private const INTERFACE_ONLY_METHODS = [
        'createMock',
        'createConfiguredMock',
        'createStub',
        'createConfiguredStub',
    ];

    /**
     * @param ReflectionProvider $reflectionProvider PHPStan reflection provider
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
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
                $this->firstArgValue($node->args),
                $node->getStartLine(),
                $scope
            );
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticCall) {
            if (!$this->isStaticCallOnCurrentTestClass($node, $scope)) {
                return [];
            }

            return $this->processCall(
                $this->resolveMethodName($node->name),
                $this->firstArgValue($node->args),
                $node->getStartLine(),
                $scope
            );
        }

        if ($node instanceof \PhpParser\Node\Expr\New_) {
            return $this->processNewExpression($node, $scope);
        }

        return [];
    }

    /**
     * @param array<array-key, \PhpParser\Node\Arg|\PhpParser\Node\VariadicPlaceholder> $args
     */
    private function firstArgValue(array $args): ?\PhpParser\Node\Expr
    {
        $first = $args[0] ?? null;

        return $first instanceof \PhpParser\Node\Arg ? $first->value : null;
    }

    private function resolveMethodName(\PhpParser\Node\Identifier|\PhpParser\Node\Expr $name): ?string
    {
        if (!$name instanceof \PhpParser\Node\Identifier) {
            return null;
        }

        return $name->toString();
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processCall(?string $methodName, ?\PhpParser\Node\Expr $firstArg, int $line, Scope $scope): array
    {
        if ($methodName === null) {
            return [];
        }

        if (in_array($methodName, self::ALWAYS_PROHIBITED_METHODS, true)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'PHPUnit %s() is prohibited. Use createMock(FooInterface::class) or createStub(FooInterface::class) instead. These APIs enforce interface-based test doubles for better decoupling.',
                        $methodName
                    )
                )
                    ->identifier('customRules.testClassPhpUnitMockProhibitedApi')
                    ->line($line)
                    ->build(),
            ];
        }

        if (!in_array($methodName, self::INTERFACE_ONLY_METHODS, true)) {
            return [];
        }

        if ($firstArg === null) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'PHPUnit %s() must use a direct interface class-string literal (e.g. DependencyInterface::class). Variables and string literals are not allowed because the type must be statically verifiable.',
                        $methodName
                    )
                )
                    ->identifier('customRules.testClassPhpUnitMockRequiresLiteralInterface')
                    ->line($line)
                    ->build(),
            ];
        }

        $targetTypeName = $this->resolveTypeNameFromExpression($firstArg, $scope);
        if ($targetTypeName === null) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'PHPUnit %s() must use a direct interface class-string literal (e.g. DependencyInterface::class). Variables and string literals are not allowed because the type must be statically verifiable.',
                        $methodName
                    )
                )
                    ->identifier('customRules.testClassPhpUnitMockRequiresLiteralInterface')
                    ->line($line)
                    ->build(),
            ];
        }

        if (!$this->reflectionProvider->hasClass($targetTypeName)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'PHPUnit %s() must target an interface; "%s" is not an interface. Mock only interfaces to keep tests decoupled from implementations.',
                        $methodName,
                        $targetTypeName
                    )
                )
                    ->identifier('customRules.testClassPhpUnitMockRequiresInterface')
                    ->line($line)
                    ->build(),
            ];
        }

        if ($this->reflectionProvider->getClass($targetTypeName)->isInterface()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'PHPUnit %s() must target an interface; "%s" is not an interface. Mock only interfaces to keep tests decoupled from implementations.',
                    $methodName,
                    $targetTypeName
                )
            )
                ->identifier('customRules.testClassPhpUnitMockRequiresInterface')
                ->line($line)
                ->build(),
        ];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processNewExpression(\PhpParser\Node\Expr\New_ $node, Scope $scope): array
    {
        if (!$node->class instanceof \PhpParser\Node\Name) {
            return [];
        }

        $resolvedName = $scope->resolveName($node->class);
        if (!in_array($resolvedName, [MockBuilder::class, MockGenerator::class], true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Direct instantiation of %s is prohibited. Use createMock(FooInterface::class) or createStub(FooInterface::class) instead.',
                    $resolvedName
                )
            )
                ->identifier('customRules.testClassPhpUnitMockProhibitedInstantiation')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function resolveTypeNameFromExpression(\PhpParser\Node\Expr $expression, Scope $scope): ?string
    {
        if ($expression instanceof \PhpParser\Node\Expr\ClassConstFetch) {
            return $this->resolveFromClassConstFetch($expression, $scope);
        }

        return null;
    }

    private function resolveFromClassConstFetch(\PhpParser\Node\Expr\ClassConstFetch $expression, Scope $scope): ?string
    {
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

    private function isStaticCallOnCurrentTestClass(\PhpParser\Node\Expr\StaticCall $node, Scope $scope): bool
    {
        if (!$node->class instanceof \PhpParser\Node\Name) {
            return false;
        }

        $className = $node->class->toString();
        if (in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
            return true;
        }

        return $scope->resolveName($node->class) === 'PHPUnit\\Framework\\TestCase';
    }
}
