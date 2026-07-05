<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Forbids descriptive (non-annotation) PHPDoc text in test classes.
 *
 * In restricted test namespaces, PHPDoc on classes and methods must contain
 * only @tags (e.g. @dataProvider, @extends). Descriptive prose text is forbidden
 * because test names and class names should be self-documenting.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassLike>
 */
final class ForbidDescriptivePhpDocInTestClassRule implements Rule
{
    private readonly DescriptivePhpDocErrorCollector $errorCollector;

    /**
     * @param list<string> $restrictedTestNamespacePrefixes namespace prefixes for test classes
     */
    public function __construct(
        array $restrictedTestNamespacePrefixes = ['Tests\\Unit', 'Tests\\Integration'],
    ) {
        $this->errorCollector = new DescriptivePhpDocErrorCollector(new RestrictedTestNamespaceMatcher($restrictedTestNamespacePrefixes));
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
        return $this->errorCollector->errors($node);
    }
}
