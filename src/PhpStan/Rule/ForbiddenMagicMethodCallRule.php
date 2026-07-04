<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<\PhpParser\Node\Expr>
 */
final class ForbiddenMagicMethodCallRule implements Rule
{
    /**
     * @var array<string, string>
     */
    private const MAGIC_METHOD_ALTERNATIVES = [
        '__construct' => 'Use the new keyword: new ClassName(...$args)',
        '__destruct' => 'Use unset() or let the object go out of scope',
        '__call' => 'Call the method by name directly: $obj->method(...$args)',
        '__callStatic' => 'Call the static method by name directly: ClassName::method(...$args)',
        '__get' => 'Access the property directly: $obj->property',
        '__set' => 'Assign the property directly: $obj->property = $value',
        '__isset' => 'Use isset(): isset($obj->property)',
        '__unset' => 'Use unset(): unset($obj->property)',
        '__sleep' => 'Use serialize(): serialize($obj)',
        '__wakeup' => 'Use unserialize(): unserialize($data)',
        '__serialize' => 'Use serialize(): serialize($obj)',
        '__unserialize' => 'Use unserialize(): unserialize($data)',
        '__toString' => 'Use (string) cast: (string)$obj',
        '__invoke' => 'Call the object as a function: $obj(...$args)',
        '__set_state' => 'Reconstruct via constructor or factory method',
        '__clone' => 'Use the clone keyword: clone $obj',
        '__debugInfo' => 'Use var_dump(): var_dump($obj)',
    ];

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
        unset($scope);

        if ($node instanceof \PhpParser\Node\Expr\MethodCall) {
            return $this->checkMethodCall($node);
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticCall) {
            return $this->checkStaticCall($node);
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkMethodCall(\PhpParser\Node\Expr\MethodCall $node): array
    {
        if (!$node->name instanceof \PhpParser\Node\Identifier) {
            return [];
        }

        $methodName = $node->name->toString();
        if (!$this->isMagicMethod($methodName)) {
            return [];
        }

        if ($this->isParentCall($node)) {
            return [];
        }

        return [$this->buildError($methodName, $node->getStartLine())];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkStaticCall(\PhpParser\Node\Expr\StaticCall $node): array
    {
        if (!$node->name instanceof \PhpParser\Node\Identifier) {
            return [];
        }

        $methodName = $node->name->toString();
        if (!$this->isMagicMethod($methodName)) {
            return [];
        }

        if ($this->isParentStaticCall($node)) {
            return [];
        }

        return [$this->buildError($methodName, $node->getStartLine())];
    }

    private function isMagicMethod(string $methodName): bool
    {
        return array_key_exists($methodName, self::MAGIC_METHOD_ALTERNATIVES);
    }

    private function isParentCall(\PhpParser\Node\Expr\MethodCall $node): bool
    {
        return false;
    }

    private function isParentStaticCall(\PhpParser\Node\Expr\StaticCall $node): bool
    {
        return $node->class instanceof \PhpParser\Node\Name
            && strtolower($node->class->toString()) === 'parent';
    }

    private function buildError(string $methodName, int $line): IdentifierRuleError
    {
        $alternative = self::MAGIC_METHOD_ALTERNATIVES[$methodName] ?? 'Use the corresponding language construct';

        return RuleErrorBuilder::message(
            sprintf(
                'Direct call to magic method %s() is prohibited. Magic methods are invoked implicitly by PHP; calling them directly bypasses language semantics. %s.',
                $methodName,
                $alternative
            )
        )
            ->identifier('customRules.forbiddenMagicMethodCall')
            ->line($line)
            ->build();
    }
}
