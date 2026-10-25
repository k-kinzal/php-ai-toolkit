<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\TestClass;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rejects non-test declarations even when PHPStan never expands an unused trait.
 * @implements Rule<ClassLike>
 */
final class PropertyTestDeclarationRule implements Rule
{
    /**
     * @return class-string<ClassLike>
     */
    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    /**
     * @param ClassLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof Class_ || $node->name === null) {
            return [];
        }
        if (!str_contains('/' . str_replace('\\', '/', $scope->getFile()), '/tests/Property/') && !str_starts_with(($scope->getNamespace() ?? '') . '\\', 'Tests\\Property\\')) {
            return [];
        }
        $name = ltrim(($scope->getNamespace() ?? '') . '\\' . $node->name->toString(), '\\');
        return [RuleErrorBuilder::message(sprintf('Declare %s as a concrete property test class extending PHPUnit\\Framework\\TestCase. Move traits, interfaces, enums, and abstract helpers out of tests/Property and Tests\\Property.', $name))
            ->identifier('customRules.propertyTestDeclaration')->line($node->getStartLine())->build()];
    }
}
