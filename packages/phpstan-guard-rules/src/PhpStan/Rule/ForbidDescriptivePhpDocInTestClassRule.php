<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use Toolkit\PhpStan\Rule\Shared\Path\RestrictedTestNamespaceMatcher;
use Toolkit\PhpStan\Rule\TestClass\DescriptivePhpDocErrorCollector;

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
    /** @readonly */
    private DescriptivePhpDocErrorCollector $errorCollector;

    /**
     * @param list<string> $restrictedTestNamespacePrefixes namespace prefixes for test classes
     */
    public function __construct(
        array $restrictedTestNamespacePrefixes = ['Tests\\Unit', 'Tests\\Integration'],
    ) {
        $this->errorCollector = new DescriptivePhpDocErrorCollector(new RestrictedTestNamespaceMatcher($restrictedTestNamespacePrefixes));
    }

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
        return $this->errorCollector->errors($node);
    }
}
