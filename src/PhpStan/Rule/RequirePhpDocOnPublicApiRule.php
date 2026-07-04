<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Requires PHPDoc comments on all public API elements.
 *
 * Checks classes, interfaces, traits, enums, their public methods
 * (including magic methods), public properties, and public constants.
 * Classes in restricted test namespaces are excluded entirely.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassLike>
 */
final class RequirePhpDocOnPublicApiRule implements Rule
{
    /** @var list<string> */
    private readonly array $restrictedTestNamespacePrefixes;

    /**
     * @param list<string> $restrictedTestNamespacePrefixes namespace prefixes to exclude from checks
     */
    public function __construct(
        array $restrictedTestNamespacePrefixes = ['Tests\\Unit', 'Tests\\Integration'],
    ) {
        $this->restrictedTestNamespacePrefixes = array_map(
            static fn (string $prefix): string => rtrim($prefix, '\\') . '\\',
            $restrictedTestNamespacePrefixes,
        );
    }
    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassLike>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassLike::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($this->isAnonymousClass($node, $scope)) {
            return [];
        }

        if ($this->isInRestrictedTestNamespace($node)) {
            return [];
        }

        $className = $node->name !== null ? $node->name->toString() : '';
        $kindLabel = $this->resolveKindLabel($node);

        return array_merge(
            $this->collectClassErrors($node, $kindLabel, $className),
            $this->collectMethodErrors($node, $className),
            $this->collectPropertyErrors($node, $className),
            $this->collectConstantErrors($node, $className),
        );
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function collectClassErrors(
        \PhpParser\Node\Stmt\ClassLike $node,
        string $kindLabel,
        string $className,
    ): array {
        if ($node->getDocComment() !== null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    '%s %s is missing a PHPDoc comment. Add a multi-line /** ... */ block describing its purpose.',
                    $kindLabel,
                    $className
                )
            )
                ->identifier('customRules.requirePhpDocOnClass')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function collectMethodErrors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $errors = [];
        foreach ($node->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            if ($method->getDocComment() === null) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Public method %s::%s() is missing a PHPDoc comment. Add a multi-line /** ... */ block describing what this method does, its parameters, and return value.',
                        $className,
                        $method->name->toString()
                    )
                )
                    ->identifier('customRules.requirePhpDocOnMethod')
                    ->line($method->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function collectPropertyErrors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $errors = [];
        foreach ($node->getProperties() as $property) {
            if (!$property->isPublic()) {
                continue;
            }

            $names = array_map(
                static fn (\PhpParser\Node\PropertyItem $prop): string => '$' . $prop->name->toString(),
                $property->props,
            );

            if ($property->getDocComment() === null) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Public property %s::%s is missing a PHPDoc comment. Add a multi-line /** ... */ block describing this property.',
                        $className,
                        implode(', ', $names)
                    )
                )
                    ->identifier('customRules.requirePhpDocOnProperty')
                    ->line($property->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function collectConstantErrors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $errors = [];
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof \PhpParser\Node\Stmt\ClassConst) {
                continue;
            }

            if (!$stmt->isPublic()) {
                continue;
            }

            $names = array_map(
                static fn (\PhpParser\Node\Const_ $const): string => $const->name->toString(),
                $stmt->consts,
            );

            if ($stmt->getDocComment() === null) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Public constant %s::%s is missing a PHPDoc comment. Add a multi-line /** ... */ block describing this constant.',
                        $className,
                        implode(', ', $names)
                    )
                )
                    ->identifier('customRules.requirePhpDocOnConstant')
                    ->line($stmt->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * Checks whether the class is in a restricted test namespace.
     */
    private function isInRestrictedTestNamespace(\PhpParser\Node\Stmt\ClassLike $node): bool
    {
        if (!isset($node->namespacedName)) {
            return false;
        }

        $fqcn = $node->namespacedName->toString();

        foreach ($this->restrictedTestNamespacePrefixes as $prefix) {
            if (str_starts_with($fqcn, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the class-like node represents an anonymous class.
     */
    private function isAnonymousClass(\PhpParser\Node\Stmt\ClassLike $node, Scope $scope): bool
    {
        if (!$node instanceof \PhpParser\Node\Stmt\Class_) {
            return false;
        }

        if ($node->name === null) {
            return true;
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection !== null && $classReflection->isAnonymous()) {
            return true;
        }

        return str_starts_with($node->name->toString(), 'AnonymousClass');
    }

    /**
     * Resolves a human-readable label for the class-like node kind.
     */
    private function resolveKindLabel(\PhpParser\Node\Stmt\ClassLike $node): string
    {
        if ($node instanceof \PhpParser\Node\Stmt\Interface_) {
            return 'Interface';
        }

        if ($node instanceof \PhpParser\Node\Stmt\Trait_) {
            return 'Trait';
        }

        if ($node instanceof \PhpParser\Node\Stmt\Enum_) {
            return 'Enum';
        }

        return 'Class';
    }
}
